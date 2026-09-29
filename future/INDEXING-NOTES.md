# Indexing notes: traps, and the settings that avoid them

Companion to `BENCHMARK.md`. That document holds the **sizing**; this one holds the **gotchas** —
things that silently misbehave and would otherwise be rediscovered the hard way.

Each item is marked by provenance:
**[M]** measured here on ES 8.15 · **[C]** carried from `rdmpage/bhl-all-the-images` (measured on a
live box) · **[D]** derived by arithmetic from a measurement.

Working configurations: `elastic/page-index.json`, `trait-search/trait-index-settings.json`.

---

## Elasticsearch

### 1. `dynamic: true` will bite you [M]

`bhl-elastic-test-index.json` sets `dynamic: true`. During this study a test index silently acquired
a `names` field simply because the bulk payload contained one — the exact failure mode, observed
accidentally. On a 63.9M-document index an unplanned field means a mapping change and a reindex.

**Use `dynamic: strict`.** Verified: an unmapped field is rejected with
`strict_dynamic_mapping_exception` rather than quietly mapped. Loud beats silent at this scale.

### 2. Disabling `_source` appears to save nothing — it isn't measured yet [M]

Two identical indexes, one with `_source` disabled, came out **the same size**. `_disk_usage`
showed why: Elasticsearch had moved 11.8 MB from `_source` into **`_recovery_source`**, which it
retains for peer recovery and prunes later.

The saving is real (~92 GB of 194 GB at full scale) but does **not** appear on a freshly built
index. Measure this after the recovery source has been pruned, or you will conclude the option is
worthless.

### 3. Force-merge before measuring anything [M]

A first size comparison showed a routed index 33% *smaller* than an unrouted one. After
force-merging to a single segment the routed index was in fact *larger*. The difference was entirely
merge state. Any index-size measurement taken before merges settle is noise.

### 4. `asciifolding` handles the ligatures; Unicode normalisation does not [M]

`Muscidæ` vs `Muscidae` is the case that matters for family names. Verified with `_analyze`:

```
in:  Muscidæ Culicidae Œstrus groß réputés Æthalion ﬁnch
out: muscidae culicidae oestrus gross reputes aethalion finch
```

No ligature `char_filter` is needed. But do **not** hand-roll this in a preprocessing step: `æ`,
`œ`, `ß`, `ø` and `đ` have **no Unicode decomposition**, so the usual "NFKD then strip combining
marks" recipe folds `réputés` correctly and leaves `Muscidæ` untouched. Lucene's
`ASCIIFoldingFilter` uses an explicit mapping table instead.

### 5. `synonym_graph` must come after `asciifolding` [M]

| chain | `Bestäubung` | `bestaubung` |
|---|---|---|
| `lowercase, asciifolding, synonym_graph` | → `bestaubung pollination` | → `bestaubung pollination` |
| `lowercase, synonym_graph, asciifolding` | → `bestaubung pollination` | → **`bestaubung` only** |

With the wrong order the *unaccented* form stops expanding — and unaccented is what BHL's
diacritic-dropping OCR produces. It would test fine with correctly-spelled German and fail on the
real corpus. Non-ASCII characters *in the rules* are safe: ES analyses the rules through the
preceding filters. Details in `trait-search/README.md`.

### 6. Entities as a plain object lose name/type correlation [M]

`bhl-elastic-test-index.json` maps `entities` as an object with `name` and `type`. Arrays of objects
flatten into parallel arrays, so the pairing is lost. Two documents, one a decoy holding
*Bombay*-as-taxon and *Muscidae*-as-place; query for an entity **named Bombay of type place**:

| mapping | result | correct? |
|---|---|---|
| plain object | `[decoy, genuine]` | **no — false positive** |
| `nested` | `[genuine]` | yes |
| **typed keyword** (`place:Bombay`) | `[genuine]` | **yes** |

`nested` is correct but expensive: measured at BHL's 8.28 names/page it stores **9× the Lucene
docs** (8 children + 1 parent), i.e. ~**575 M** for 63.9 M pages.

**Encode the type into a single keyword value** — `taxon:Ansonia anotis`, `place:Borneo`. Exact
correlation, no doc inflation, still prefix-filterable. Verified in `elastic/page-index.json`.

### 7. Don't mix pages, items and parts in one index [D]

A `type` keyword discriminating page/item/part documents means BM25 computes IDF and field-length
norms across documents whose `text` differs by orders of magnitude, distorting length normalisation
for all of them. Separate indexes behind an alias score better and cost the same.

### 8. The scripted `max(_score)` aggregation is fine — my concern was wrong [M]

Measured at 496K docs on 5 shards, across queries matching 5%, 24% and 44% of the corpus:

| approach | latency |
|---|---|
| terms agg + scripted `max(_score)` + `top_hits` | **1–4 ms** |
| `collapse` + `inner_hits` | 27–111 ms |

`collapse` loses because `inner_hits` runs an **extra search per collapsed group** — 20 groups, 20
extra queries — while `top_hits` collects during the pass already happening. Keep the aggregation.

Ordering by the sub-aggregation was also **accurate at default `shard_size`**, matching a
`shard_size=5000` ground truth. That is specific to `max`: a globally top item necessarily has a
high local max on whichever shard holds its best page. **It does not generalise** — ordering by
`avg`, `sum` or `cardinality` is genuinely unreliable. Keep the ordering metric a `max`.

*Caveat:* measured at 496K docs fully in page cache. `collapse`'s cost is fixed per group while the
aggregation's grows with matching documents, so the ranking could invert at 63.9M. Re-check at 10M+.

### 9. Routing by `itemid` is not worth it [M]

Tempting (co-locate an item's pages) and measurably not worth it: after force-merge the routed index
was **larger** (906 MB vs 780 MB), `text` field bytes were within 1%, and shard occupancy skewed to
49K–67K docs against an even 60K. No latency gain either.

### 10. Leave `track_total_hits` at its default [M]

The 10,000 cap is correct and `bhl-elastic-test-query.json` already relies on it. Forcing
`track_total_hits: true` is what made broad queries expensive in benchmarking — one query matched
110,342 passages. The exact count is rarely worth it.

### 11. Slicing finer is nearly free; in a vector index it is not [M/D]

The same text as 64M pages vs 205M passages costs 215 GB vs 231 GB in Elasticsearch — but 144 GB vs
462 GB of HNSW that **must be resident**. An inverted index streams from disk and degrades
gracefully; HNSW does thousands of random hops per query and falls off a cliff. This asymmetry is
why trait search is passages-in-Elasticsearch rather than fine-grained embeddings.

### 12. Geospatial is effectively free [M]

Measured on BHL-Light's 6,097 `geotagged/` documents: **80% of items have no coordinates at all**,
**0.514% of pages** carry one, 2.91 points each. For 63.9M pages that is ~328K geotagged pages and
~955K `geo_point` values ≈ **29 MB**. A `geotile_grid` at `precision: 5` over the whole geo subset
measured **2 ms**.

The limit is coverage, not cost. 0.5% is a property of the `preg_match_all` extraction — explicitly
written coordinates are a 20th-century habit. Representing where content is *about* needs
gazetteer-based georeferencing, a much larger undertaking.

### 13. Grouping by part covers only 7.4% of pages [M]

`bhlv:hasPage` = **4,756,621** page↔article links against 63,862,938 pages. Item-level grouping must
be the default; a `by_part_id` view given equal billing would look broken on 93% of searches.

### 14. Measured throughputs, for planning [M]

| operation | rate |
|---|---|
| CouchDB → page JSONL (`extract-pages.php`) | 2,494–2,876 pages/s |
| bulk index, 8-core laptop | ~10,000 docs/s |
| passage build + index, 242,815 passages | ~50 s |

63.9M pages therefore extract in roughly 7 hours and index in under 2 — indexing is not the long
pole. Turn `refresh_interval` off during bulk loads and force-merge afterwards.

### 25. Static page weights (inbound links) cost essentially nothing [M]

Sparse per-page weights — inbound links from Wikipedia, Wikispecies, taxonomic databases — are
cheap to rank with. Measured on 2,428,150 passages, 4 shards, with **2% of documents carrying a
power-law-distributed link count** (most 1–2, a few in the thousands), which is the realistic shape.

**Storage is free.** After force-merge the `rank_feature` field did not even register in
`_disk_usage`; a parallel plain integer field cost 25 KB across 2.4M documents.

**Query cost, median/p90 ms, against baseline BM25:**

| query | matches | baseline | `rank_feature` | `function_score` | `script_score` |
|---|---|---|---|---|---|
| narrow phrase | 56,540 (2%) | 24/29 | 24/28 | 20/32 | 20/29 |
| medium | 1,310,000 (54%) | 40/58 | 56/75 | 44/53 | 34/38 |
| broad term | 400,430 (16%) | 2/3 | 3/4 | 4/5 | 7/8 |
| very broad | 1,562,200 (64%) | **5/7** | **32/37** | 34/37 | 32/36 |

**Realistic queries show no meaningful penalty.** The only real effect is on a pathological query
matching 64% of the corpus, and it is worth understanding *why*: the unweighted baseline is
unusually fast there (5 ms) because Lucene's block-max WAND lets it skip almost everything when only
the top 10 are wanted. Adding *any* score contribution removes that shortcut and forces scoring of
all 1.56M matches. So the 6.4× ratio is the baseline being fast, not the weighting being slow.

Scaling per-shard: 63.9M pages across ~24 shards is ~4.4× the per-shard load tested here, putting
the worst case around **140 ms against ~22 ms unweighted** — imperceptible, and only for queries no
one actually issues.

**Correction to an assumption:** I expected `rank_feature` to preserve WAND where `function_score`
would not. Measured, all three were affected equally. Choose on semantics, not speed.

### 25a. Use `rank_feature`, and three things to get right [M]

Prefer `rank_feature` over `function_score`/`script_score` for reasons other than latency:

1. **Missing values are native.** Verified: as a `should` clause it does **not** filter — a query
   matched 78,140 documents with and without it. `function_score` needs a `missing: 0` hack.
2. **`saturation` is the right shape for link counts.** They are power-law: a handful of pages have
   thousands of links. A raw multiplier lets one page dominate every result set; `saturation` with a
   pivot gives diminishing returns, so the 5,000-link page beats the 50-link page without burying
   everything else. Set `pivot` near the *median count among pages that have links*, not the corpus
   median (which is 0).
3. **`rank_feature` rejects zero and negative values.** Verified — indexing `inlinks: 0` fails with
   `failed to parse field [inlinks] of type [rank_feature]`. **Omit the field entirely for unlinked
   pages**; do not store 0. Given most pages will have no links, this is the detail most likely to
   break a bulk load.

Confirmed working: with the boost applied, a linked page rose into the top 5 where the baseline had
none (scores 16.93 vs 16.2).

```json
{ "bool": {
    "must":   [ { "...": "the text query" } ],
    "should": [ { "rank_feature": {
        "field": "inlinks",
        "saturation": { "pivot": 8 },
        "boost": 4 } } ] } }
```

Tune `boost` against the judged query set (`trait-search/eval/`) — a static prior that overrides
textual relevance is worse than none, and the harness will show it.

---

## Image embedding index (pgvector)

Not re-measured here — these carry from `rdmpage/bhl-all-the-images`, which measured them on a live
589,043-vector box, plus arithmetic on top.

### 15. The HNSW cost model [D, from C]

From the measured 784 MB / 589,043 `halfvec(512)` vectors:

```
bytes per vector  =  dim × 2  +  372
                     ^^^^^^^     ^^^
                     vectors     navigation graph — INDEPENDENT of dimension
```

This reproduces the published 80 GB full-corpus figure exactly. Two consequences:

- "HNSW ≈ 2× raw vectors" **overstates it at low dimensions** — measured 1.36× at 512-d.
- The graph term is why compression disappoints: it does not shrink with the vectors.

### 16. Binary quantisation is rejected on measurement [C]

| | index size | recall@12 corpus | recall@12 **text** |
|---|---|---|---|
| `halfvec` HNSW | 784 MB | 0.985 | **0.875** |
| bit + re-rank F=500 | 207 MB | 0.909 | **0.736** |

The index shrinks only **3.8×**, not 16×, because the graph dominates (item 15). And text queries —
the ones users actually send — land in sparse regions where sign-bit approximation breaks. A quarter
of the best results go missing even re-ranking 500 candidates.

**Not refuted for text embeddings:** that experiment was CLIP image vectors queried by CLIP text, a
notoriously anisotropic space. SBQ on BGE-M3-style text-to-text is a different geometry and remains
the one unexplored lever.

### 17. `hnsw.ef_search` must be 300, not the default 100 [C]

At 100 the measured recall@12 on real text queries was only 0.875. Raising it to 300 recovers most
of the gap for a few milliseconds.

### 18. CLIP cosine on this corpus is anisotropic [C]

**Two random BHL pages average ~0.61 cosine, not 0**, because the corpus is overwhelmingly scanned
text. Never set an absolute similarity threshold on raw scores — use ranking, or mean-centre the
vectors (subtract the corpus mean, renormalise) which re-centres random pairs near zero.

### 19. Whole-page thumbnails work; read the webp, not the JP2 [C]

150×234 thumbnails gave working visual-similarity and text-to-image retrieval over 218,566 pages.
Embedding is **decode-bound**, and webp decodes 20–40× faster than JPEG-2000 — which turns a ~$470
full-corpus Spot run into **$25–50**. `_medium` (~465px) measured p@5 0.60 against 0.63 for full JP2;
`_small` is lossy at 0.53. CLIP downsamples to 224px anyway, so the JP2's resolution is wasted.

### 20. Figure regions, and the two image corpora [M]

Measured across all 22 `blocks/` documents (2,805 pages): **0.87 `Picture`+`Figure` per page**, 0.34
captions, 47% of pages carrying at least one. Those items are figure-biased and some labels sit at
0.4–0.7 confidence, so the honest range is **0.3–0.87/page** → 18–52M crops for BHL, the same order
as the page count.

Page images measured at **median 2561 × 4000 px (~10 MP)**, webp 56–170 KB (mean ~100 KB). So the
webp derivative set is roughly **6.4 TB** for 63.9M pages — against the ~100 TB of archival JP2.
Worth keeping the two numbers distinct in any plan that involves moving images.

### 21. The AWS → Hetzner pipeline works as designed [C]

Confirmed against the repo: embed in-region on Spot in `us-east-2` reading the `bhl-open-data` webp
derivatives, write Parquet shards to your own S3, then `db/schema_hetzner.sql` (bare `halfvec(512)`
table) → `hetzner/load_parquet.py` (COPY every shard) → `db/index_hetzner.sql` (PK + HNSW). Only
vectors leave AWS. `hetzner/dry_run.md` is the validated end-to-end runbook.

**Scheduling:** the AWS half does not depend on the machine existing. Vectors are just Parquet on S3
until there is somewhere to put them, so the embedding job can run before delivery.

### 22. HNSW build time at 60M — measured, extrapolated, and it hinges on one setting [M]

The largest schedule unknown, now bounded. Measured on pgvector 0.8.6 / PG 17 with **synthetic
512-d vectors calibrated to BHL's real anisotropy** (mean pairwise cosine 0.605 against the measured
0.61 — uniform random vectors would not build a representative graph).

**Scaling, graph resident in `maintenance_work_mem`, 7 workers on an 8-core M1:**

| rows | time | rows/s |
|---|---|---|
| 200,000 | 25 s | 8,000 |
| 500,000 | 78 s | 6,410 |
| 1,000,000 | 171 s | 5,848 |
| 2,000,000 | 371 s | 5,391 |

Power-law fit **t = 1.61e-5 · n^1.170**, predicting all four points within 3%. The exponent > 1
confirms the build is superlinear, so per-row cost keeps rising with corpus size.

**Parallel scaling at 1M rows:** 1 process 563 s → 4 processes 195 s (2.89×) → 8 processes 171 s
(3.29×). Sharply diminishing, partly because the M1's 4 efficiency cores contribute little. An
Amdahl fit to the 4-core point (f = 0.872) projects **5.5× at 16 cores**.

**Extrapolated to 60M rows on the Ryzen 9 9950X:** ~5.6 h on this M1, divided by ~1.66× more
parallel scaling and ~1.3× per-core, gives **roughly 2–4 hours with the graph resident.** So the
repo's "budget a few hours" is **correct — conditionally.**

### 22a. The condition: the graph must fit, and `/dev/shm` gates it [M]

**Measured spill penalty at 1M rows** (graph 1.37 GB):

| `maintenance_work_mem` | time | |
|---|---|---|
| 4 GB (graph fits) | 171 s | |
| 128 MB (~10% of graph) | **867 s** | **5.07× slower** |

`db/index_hetzner.sql` sets **8 GB against a 78 GB graph — the same ~10% ratio.** Applied to the
60M projection that turns **~2–4 hours into ~13–20 hours.** Fix the setting.

**And a trap that would otherwise cause exactly that:** pgvector's *parallel* build allocates
`maintenance_work_mem` in **shared memory**, so `/dev/shm` must be at least that large. Setting
`maintenance_work_mem=4GB` against a 2 GB `/dev/shm` failed outright with:

```
ERROR: could not resize shared memory segment ... No space left on device
```

On bare-metal Linux `/dev/shm` defaults to **50% of RAM**, i.e. **64 GB on a 128 GB box — less than
the 78 GB graph.** So the default silently caps `maintenance_work_mem` below what the build needs.
Raise it explicitly before building:

```bash
sudo mount -o remount,size=100G /dev/shm      # then set maintenance_work_mem accordingly
```

**Recommended for the full run:** `/dev/shm` ≥ 100 GB, `maintenance_work_mem` 80 GB,
`max_parallel_maintenance_workers=15`, Elasticsearch stopped, and build the HNSW index before the
box has other services competing for RAM.

**Caveats on these numbers.** Docker on macOS adds virtualization overhead; `fsync` and
`synchronous_commit` were off (optimistic); vectors are synthetic though distribution-matched; the
60M figure extrapolates 30× beyond the largest measured point; and the 16-core projection is an
Amdahl model, not a measurement. Treat **2–4 hours** as the defensible range, not a point estimate.

Index size came out at a consistent **1,366 B/vector** across every build — within 2% of the
`dim × 2 + 372` model in item 15, which is a useful independent confirmation of the sizing.

### 23. The export is float32 — ~115 GB, just over the free egress tier [M]

`aws/embed_s3.py` writes `pa.list_(pa.float32(), 512)`. For 60M pages that is **~115 GB** to move,
against AWS's **100 GB/month free egress** — an overage of roughly $1.35. Trivial, but worth knowing
rather than discovering.

Emitting **float16 gives ~58 GB**, free, and matches the `halfvec(512)` column it is loaded into
anyway. CLIP vectors are L2-normalised and `schema_hetzner.sql` already notes they tolerate fp16
with negligible recall loss, so that precision is discarded at load either way.

### 24. One stale comment to fix [C]

`db/index_hetzner.sql` ends with `SET hnsw.ef_search = 100`. The recall study (item 17) found 100
gives only 0.875 recall@12 on text queries and the serving default was raised to **300**. The SQL
comment now contradicts `hetzner/README.md`.
