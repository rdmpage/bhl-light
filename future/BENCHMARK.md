# BHL server sizing: answers to SIZING-STUDY.md

Response to the brief in `SIZING-STUDY.md`. Measured 2026-09-28 against the local `bhl-lite`
CouchDB, the QLever BHL endpoint, and the prior work in
[`rdmpage/bhl-all-the-images`](https://github.com/rdmpage/bhl-all-the-images).

**Headline: trait search is Elasticsearch passages + a curated multilingual synonym thesaurus for
ranking, plus a small *targeted* dense index for recall. No reranker, no corpus-wide embeddings.**

Measured, on 242,815 real BHL passages with blind relevance judgments:

- **BM25 + trait synonyms wins ranking** at nDCG@10 **0.868**. A cross-encoder reranker *lowers* it
  to 0.760–0.836 — OCR noise makes the model prefer clean front matter over garbled fact statements.
- **Dense retrieval adds real recall** — 3.8 relevant passages per query that lexical never
  surfaced — but at lower precision, and it failed completely on the one cross-language query.
- Capturing that recall corpus-wide costs 107 GB+ resident and does not fit. **A trait-flagged
  subset costs ~14 GB and does.**

Net: hard residency is **78 GB** (image vectors), so **128 GB is right** and the quoted machine is
correct. Binary quantisation is rejected on measurement. Disk ~0.9–1.8 TB live, so 8 TB is generous.
One box throughout.

Assumptions updated 2026-09-28 after discussion:

- **Datalab is out.** Assume the NHM London Mistral 3 run *may* happen, and design so that nothing
  is blocked if it doesn't (§2). LLM-based OCR generally does not emit word- or line-level
  coordinates, so the direction of travel is from *image + word coordinates* to **image +
  Markdown**. Page-level geometry still comes from the scans themselves, which is all IIIF needs.
- **Page geometry is in the triple store** after all — IIIF and annotation need it, and it costs
  3–6 GB in QLever, so the brief's objection doesn't survive (§4).
- **QLever is the RDF store**, and it is *not* the reason to buy big drives. It is the cheapest
  component here by an order of magnitude (§4).
- **`asciifolding` and fuzziness stay regardless of Mistral** — `-idæ` vs `-idae` is not fixable by
  Unicode normalisation, and the verification is in §1.
- **The dense text-embedding layer and the reranker are both cut** (§3a). Measured: BM25 + a trait
  synonym thesaurus beats every reranked variant on this corpus. Residency drops to 78 GB, which is
  what makes the quoted 128 GB machine the right buy rather than a compromise.

**Companion documents:** [`INDEXING-NOTES.md`](INDEXING-NOTES.md) collects the operational traps —
the things that silently misbehave — with working configurations in [`elastic/`](elastic/) and
[`trait-search/`](trait-search/). This document holds the sizing; that one holds the gotchas.

## Evidence base

Three independent sources, deliberately cross-checked:

1. **BHL-Light, measured today.** 120 random `layout/` docs of 6,507, all 22 `blocks/` docs,
   60 `nametagged/` docs. Extractor: `future/extract-pages.php`. Cross-check: extrapolated
   layout JSON 11.7 GB vs the database's actual 13.4 GB external size.
2. **Full-BHL counts, measured** from `bhl://schema` on the QLever endpoint — exact class and
   predicate cardinalities, not estimates.
3. **`bhl-all-the-images`, measured** on a live box: 218,566 thumbnails embedded, then a
   589,043-vector HNSW index profiled and a binary-quantisation recall study run (2026-06-29).

### Corpus scale — task answered, replacing the brief's "roughly 60–64M"

| | measured | source |
|---|---|---|
| pages (`bibo:Page`) | **63,862,938** | QLever |
| titles (`bhlv:Title`) | 204,710 | QLever |
| items (`bibo:Book`) | 329,364 | QLever |
| articles (`bibo:Article`) | 442,868 | QLever |
| creators (`foaf:Agent`) | 276,341 | QLever |
| triples | 771 M | QLever |
| pages after blank filter | ~60 M | bhl-all-the-images |

BHL-Light is **2,259,502 pages = 3.54% of BHL**, 6,557 items = 2.0%. Scale factor **×28.3**.

### Per-page measurements from BHL-Light

| | measured | brief's assumption |
|---|---|---|
| OCR text/page | **2,254 bytes** | 2–3 KB — **confirmed** |
| words/page | 355 (≈474 tokens) | — |
| OCR lines/page | 14.9 | — |
| pages with tagged names | 72% | — |
| `Picture`+`Figure`/page | **0.87** | "the biggest unknown" |
| `Caption`/page | 0.34 | — |
| layout blocks/page | 12.7 | — |

Full corpus: **134 GB of OCR text, 22.7 B words.**

## 1. Elasticsearch — confirmed, no surprises

The mapping is already settled in `bhl-elastic-test` (page-level docs, `folding` analyser,
`index_options: offsets`, query-time fuzziness).

**These are now measured, not estimated.** 8,269 real BHL pages (17.2 MB of OCR text) were indexed
into ES 8.15 under five mapping variants, force-merged to one segment, and broken down with
`_disk_usage`. Text-proportional components are scaled ×1.082 because the slice averages
2,083 B/page against the corpus's measured 2,254 B/page.

| component | B/page | projected to 63.9 M pages |
|---|---|---|
| `text` field (postings + offsets + norms) | 1,560 | **93 GB** |
| `_source` (stored original) | 1,552 | 92 GB |
| `names` (keyword + text) | 126 | 8 GB |
| `_id`, `_seq_no`, other | 29 | 2 GB |
| **design mapping total** | **3,267** | **194 GB** |
| + 5–10 n-gram subfield | +4,948 | **+294 GB** |
| = design + n-gram | 8,215 | **489 GB** |

The brief's 150–300 GB (no n-gram) and 0.5–1 TB (with n-gram) are both **confirmed**, and my own
earlier estimates (208 GB / 536 GB) were within 7–10%. Two things the measurement adds that the
estimate missed:

- **`offsets` cost 23%** over positions-only (1,560 vs 1,268 B/page). That is the price of fast
  highlighting, and highlighting is the feature that makes page-level search usable. Worth it.
- **The `names` subfield is nearly free — 8 GB, not the ~20 GB I guessed.** At ~8 names/page it is
  noise next to the text. Index it without hesitation.

**Careful with `_source`.** Disabling it *looks* like it saves nothing: the `b_nosource` variant came
out the same size as the design mapping. `_disk_usage` shows why — ES had simply moved 11.8 MB from
`_source` into **`_recovery_source`**, which it retains for peer recovery and prunes later. The real
saving is genuine (~92 GB, taking the index to ~102 GB) but it does not appear on a freshly built
index, so don't measure this on a fresh load and conclude the option is worthless.

Heap: 31 GB (stay under the compressed-oops limit). Page cache wants as much of 194 GB as it can
get — the second claim on RAM after the vector indexes.

### Keep `asciifolding` and fuzziness regardless of Mistral

`Muscidæ` vs `Muscidae` is the case that settles this, and it is sharper than it looks.

**Ran the brief's `_analyze` check on ES 8.15. The `folding` analyser handles all of it — no
separate ligature `char_filter` is needed:**

```
in:  Muscidæ Culicidae Œstrus œstrus groß réputés Échange Æthalion Nahrung ﬁnch Ræbelia
out: muscidae culicidae oestrus oestrus gross reputes echange aethalion nahrung finch raebelia
```

`Muscidæ` and `Culicidae` fold to the same token shape, so `-idæ` and `-idae` collide as required.

Why this needed checking rather than assuming, and why it must be `asciifolding` specifically:

| character | Unicode decomposition | NFKD folds it? |
|---|---|---|
| `é` U+00E9 | `0065 0301` | yes |
| `ﬁ` U+FB01 | `<compat> 0066 0069` | yes |
| **`æ` U+00E6** | **none** | **no** |
| **`œ` U+0153** | **none** | **no** |
| `ß` U+00DF | none | no |
| `ø` U+00F8, `đ` U+0111 | none | no |

`æ` and `œ` have **no Unicode decomposition at all**, so the usual "NFKD then drop combining marks"
recipe leaves `Muscidæ` unchanged while correctly folding `réputés`. Lucene's `ASCIIFoldingFilter`
does not rely on NFKD — it carries an explicit mapping table (`æ`→`ae`, `œ`→`oe`, `ß`→`ss`,
`ø`→`o`, `đ`→`d`), which is exactly why the `folding` analyser is the right tool and why
hand-rolling the normalisation in a preprocessing step would silently fail on the one case that
matters most for family names.

And **keep query-time fuzziness whether or not Mistral 3 happens.** Cleaner transcription reduces
the need for it but doesn't remove it: fuzziness costs nothing at index time, so it is insurance
with no carrying cost. The thing to defer pending Mistral is the **n-gram subfield** (330 GB), not
the analyser chain. Fix the 330 GB figure at the measured **+294 GB**.

**On hosting vectors in ES instead of Postgres:** don't. ES `dense_vector` HNSW carries the same
graph cost with less control over quantisation, and `bhl-all-the-images` already has a validated
pgvector + FastAPI serving path with a systemd unit and a PHP demo. No reason to re-litigate.

## 1b. The `bhl-elastic-test` design reviewed — mostly it holds

Reviewing `bhl-elastic-test-index.json` / `-query.json` against a 300–496 K-doc index on 5 shards,
built from the real page slice with BHL's measured part rate (7.4%) and geotag rate (0.51%).

### Three concerns I had, all measured and all wrong

| | measured | verdict |
|---|---|---|
| The scripted `max(_score)` agg will be slow at scale | **1–4 ms** at 5%, 24% and 44% corpus match | **no problem — it was the fastest option** |
| `collapse` would be faster than the terms agg | collapse **27–111 ms** vs agg **1–4 ms** | **collapse is 10–30× slower** |
| terms-agg-ordered-by-sub-agg gives wrong ordering | top-20 matched a `shard_size=5000` ground truth at **default `shard_size`** | **accurate as written** |

- **Why collapse loses:** `inner_hits` triggers an extra search per collapsed group, so 20 groups cost
  20 additional queries. `top_hits` inside the terms agg collects during the single existing pass.
- **Why the ordering is safe:** `max` is well behaved for shard-local selection — an item that is
  globally top-scoring necessarily has a very high local max on whichever shard holds its best page,
  so it makes that shard's top-40 easily. **This does not generalise**: ordering a terms agg by
  `avg`, `sum` or `cardinality` *is* unreliable, because a shard-local value there can be
  systematically misleading. Keep the ordering metric a `max`.
- **Routing by `itemid`** looked attractive (co-locate an item's pages) and is **not worth it**: after
  a proper force-merge the routed index was *larger* (906 MB vs 780 MB), `text` field bytes were
  within 1%, and shard occupancy skewed to 49 K–67 K docs against an even 60 K. An apparent 33%
  saving before merging completed was merge state, not routing.
- `track_total_hits` is left at its 10,000 default, which is **right**. Forcing it true is what makes
  broad queries expensive; the count is not worth it.

**Caveat:** these are 300–496 K docs on one node entirely in page cache. At 63.9 M the absolute
numbers rise, and collapse's *fixed* per-group cost becomes relatively less significant while the
agg's per-matching-doc cost grows — so the ranking between them could invert. The conclusion "keep
the agg" is safe for now; re-run this at 10 M+ before treating it as settled.

### Four things I would change

1. **`dynamic: true` → `dynamic: strict`.** On a 63.9 M-doc index an unexpected field means a
   mapping explosion and a reindex. My own earlier test index silently acquired a `names` field this
   way, which is exactly the failure mode. The scale test ran `strict` without friction.
2. **`entities` is an object, not `nested`, so `name`/`type` correlation is lost.** An array of
   `{name, type}` flattens into parallel arrays, so `type:place AND name:Bombay` will match a page
   that has *some* place entity and *separately* a Bombay taxon. Making it `nested` is correct but
   expensive — at 8.28 entities/page that is ~529 M extra Lucene docs. **Cheaper fix: encode the
   type into the keyword value** (`place:Bombay`, `taxon:Muscidae`) in a single keyword field. That
   preserves correlation exactly, costs nothing, and stays filterable by prefix.
3. **Split the polymorphic index.** `type` mixing pages, items and parts in one index means BM25
   computes IDF and field-length norms across heterogeneous documents — an item-level `text` and a
   page-level `text` differ in length by orders of magnitude, which distorts length normalisation
   for both. Separate indexes behind an alias score better and cost the same.
4. **Add `partid` to the passage index too** (§3a), so passage hits can be grouped the same way.

### Geospatial — a gap in my earlier sizing, and it is free

I had omitted this entirely. Measured on the 6,097 `geotagged/` documents in BHL-Light:

| | measured |
|---|---|
| items with **no** coordinates at all | **80%** |
| pages carrying ≥1 coordinate | **0.514%** |
| coordinate points per geotagged page | 2.91 |

Projected to 63.9 M pages: **~328 K geotagged pages, ~955 K `geo_point` values**. At ~30 B/point that
is **~29 MB indexed**, plus a few hundred MB of `geo_annotations` sitting un-indexed in `_source`.
Negligible — it does not appear in the rollup. A `geotile_grid` at `precision: 5` over the whole geo
subset measured **2 ms**, so the README's claim that the map view scales to the corpus is correct:
the aggregation only ever touches the 0.5% of pages that have coordinates.

**The real limit is coverage, not cost.** 0.5% is a property of the `preg_match_all` extraction —
it finds explicitly written coordinates, which are a 20th-century taxonomic habit. If the map is
meant to represent where BHL's content is *about*, that needs gazetteer-based georeferencing of
place names, which is a different and much larger undertaking (and a much larger `locations` field).
Worth deciding which of the two the feature is for before building the UI around it.

### Clustering by part covers only 7.4% of pages

From the RDF census: `bhlv:hasPage` = **4,756,621** page↔article links against 63,862,938 pages. So
grouping results by **article works for 7.4% of the corpus**, and by **item for all of it**.

That is a UX constraint rather than a sizing one, and it argues for item-level grouping as the
default with article-level as an enrichment where `partid` exists — not a choice between them. It
also means the `by_item_id` aggregation in the existing query is the right primary view, and a
`by_part_id` equivalent would look broken on 93% of searches if given equal billing.

## 2. Image vectors — the brief's biggest unknown, measured

**Figures per page = 0.87** (`Picture` 0.60 + `Figure` 0.27), captions 0.34, across all 22
`blocks/` docs / 2,805 pages. 47% of pages contain at least one. Those 22 items are figure-biased
and some `Picture` labels sit at 0.4–0.7 confidence on probable text pages, so treat 0.87 as an
upper bound; the honest range is **0.3–0.87 per page**.

So the answer to "more than 64M vectors or far fewer?" is **neither — it is the same order of
magnitude**: 18–52 M figure crops against 60 M pages. Figure-level does not blow up the sizing,
and it does not shrink it either.

| what you index | vectors | HNSW (512-d halfvec) |
|---|---|---|
| pages only | 60 M | **78 GB** |
| pages + figures (low) | 78 M | 101 GB |
| pages + figures (high) | 112 M | 146 GB |

The HNSW cost model is derived from `bhl-all-the-images`' measured 784 MB / 589,043 vectors:
**bytes/vector = dim × 2 + 372**, where 372 B is the navigation graph and is *independent of
dimension*. That model reproduces their 80 GB full-corpus figure exactly. It also means the
brief's "HNSW index ≈ 2× raw vectors" overstates it at 512-d: measured is 1.36×, so **78 GB, not
128 GB**.

### Correction: whole-page embeddings work

The brief leaves page-vs-figure open, and in an earlier draft of this document I argued whole-page
embeddings would be "near-useless" because CLIP downsamples a 10 MP scan to 224 px. **That was
wrong, and `bhl-all-the-images` had already measured it:** 218k page thumbnails at *150×234* give
working visual-similarity and text-to-image retrieval ("a map", "a portrait of a person"), with
p@5 0.60 at `_medium` (465 px) against 0.63 for full JP2. Page-level is proven. Figure crops are
an *enhancement* for the cross-domain case (organism photo → published illustration), which is
the one place ViT-B/32 is genuinely weak.

Two caveats carried forward from that work: CLIP on this corpus is strongly anisotropic (two
random pages average **0.61 cosine**, not 0), so thresholds must be set on ranking or on
mean-centred vectors; and `hnsw.ef_search` must be **300**, not the default 100, which measured
only 0.875 recall@12 on text queries.

### Correction: binary quantisation is rejected

The brief recommends binary quantisation or DiskANN/SBQ so a small compressed index stays in RAM.
**`bhl-all-the-images` tested exactly this and rejected it**, which supersedes the brief:

| | index size | recall@12, corpus | recall@12, text |
|---|---|---|---|
| halfvec HNSW | 784 MB | 0.985 | **0.875** |
| bit + re-rank F=100 | 207 MB | 0.740 | 0.458 |
| bit + re-rank F=500 | 207 MB | 0.909 | **0.736** |

Two reasons it fails. The index only shrinks **3.8×**, not 16×, because the graph (~181 MB of the
784 MB) doesn't compress — the same dimension-independent 372 B/vector above. And text queries,
the ones users actually send, land in sparse regions where sign-bit approximation breaks: a
quarter of the best results go missing even re-ranking 500 candidates.

**Nuance worth testing separately:** that result is for *CLIP image vectors queried by CLIP text*,
a notoriously anisotropic geometry. BGE-M3 text-to-text is a different space, so SBQ on the
**text** vectors is not refuted by this and is the one lever left if you stay at 128 GB.

## 3. Text vectors — the component that breaks the 128 GB box

BGE-M3, 1024-d, page level, halfvec HNSW, 60 M pages:

| dim | HNSW |
|---|---|
| 512 | 78 GB |
| 768 | 107 GB |
| **1024 (BGE-M3)** | **135 GB** |

Paragraph level at ~5 text blocks/page is ~360 M vectors ≈ **0.8 TB of index** — off the table for
one machine, exactly as the brief suspects. The brief's own compromise (embed only flagged
trait-bearing paragraphs) is the right shape, and its "check whether embeddings beat BM25 plus a
synonym list first" is the cheapest experiment in the whole study. Do that before spending 135 GB.

## 3a. The trait / ecological-relationship use case without dense vectors

The target queries are things like *what does this animal eat*, *which plant hosts this insect*,
*where does it breed* — phrased in any of a dozen ways, across English, German, French and Latin,
and not marked up as annotations. This is the case the embedding layer was meant to serve, and it
is worth stating precisely why it is awkward, because the diagnosis picks the architecture.

### BM25 and page-level dense embeddings fail for *different* reasons

- **BM25's weakness is vocabulary.** "Feeds chiefly on termites" does not match a query for
  *diet ants*, and matches nothing at all for *Nahrung*.
- **Page-level dense embedding's weakness is dilution.** A trait statement is usually one clause in
  a page of 474 tokens of unrelated taxonomic description. Averaging that page into a single
  1024-d vector washes the clause out. Dense retrieval is weakest exactly where the signal is a
  small, specific span inside a long, generic passage — which is this use case.

The fix for dilution is smaller chunks. That is precisely what makes the dense route expensive,
and here is the asymmetry that resolves the whole question:

| retrieval units | Elasticsearch index | dense HNSW (1024-d halfvec) |
|---|---|---|
| 64 M pages | 215 GB — **on disk**, page-cached | 144 GB — **must be resident** |
| 205 M merged ~150-token passages | 231 GB on disk | 462 GB resident |
| 360 M text blocks | 248 GB on disk | 811 GB resident |

(Passage counts from the measured layout blocks: 5.64 `Text` + 2.04 `ListItem` per page, or
~3.2 passages/page if merged to ~150 tokens. ES figures are text × 1.55 plus ~120 B/doc overhead;
HNSW uses the validated dim × 2 + 372 B/vector model.)

**Slicing finer costs almost nothing in an inverted index and multiplies RAM in a vector index.**
An inverted index streams from disk and degrades gracefully when it doesn't fit page cache; HNSW
does thousands of random hops and falls off a cliff. So: chunk finely in Elasticsearch, and buy the
semantic lift somewhere other than a corpus-wide dense index.

### The recommended stack, cheapest first

1. **Index passages as well as pages.** A second index (`bhl-passages`, ~200–360 M docs) carrying
   one paragraph/block each, with `page_id`, item, title, year and the page's names denormalised
   onto it. Same text, finer units, ~231–248 GB. ES handles 360 M docs on one node comfortably
   (the per-shard ceiling is ~2.1 B). This alone fixes dilution, and it is the single biggest win.
2. **Query-time `synonym_graph` over a curated trait thesaurus.** *diet, food, feeds on, feeding
   habits, stomach contents, gut contents, prey, Nahrung, nourriture, victus*. Multi-word synonyms
   need `synonym_graph`, not `synonym`, and it must be a **search** analyser, not an index
   analyser — then the thesaurus is editable without reindexing. Zero index cost, fully
   inspectable, and domain thesauri already exist to seed it. This is the brief's own proposal and
   it remains the best value per unit of effort.
3. **Cross-encoder reranking on the candidates.** Retrieve top 50–200 passages lexically, rescore
   with a multilingual cross-encoder. **This is where the actual paraphrase and cross-language
   understanding happens** — and it costs *no storage and no resident RAM*, because it only ever
   sees the candidates. It is, however, the one genuinely CPU-hungry piece in the design — **now
   measured** at 40 ms/passage (`bge-reranker-base`, ONNX int8, M1 CPU) and 175 ms/passage for
   `bge-reranker-v2-m3`. Cap the pool at 50 and `max_length` at 192. See *Reranker latency* below;
   the model choice is unresolved and swings this by 3.6×.
4. **Learned sparse expansion, only if step 3's candidate recall proves too low.** Stored in a
   `sparse_vector` field, this is inverted-index-shaped — disk and page cache, **no HNSW graph**,
   so it sidesteps the residency problem entirely. Cost is roughly +100% on the text index.
   **Caveat that matters here: ELSER is English-only**, which rules it out as the primary
   mechanism for a corpus this multilingual. The multilingual route is BGE-M3's *sparse* output
   (it emits sparse lexical weights alongside the dense vector) or a multilingual SPLADE.

Steps 1–3 need **no dense vector index at all**. That is the point.

### Step 2 validated on ES 8.15

A `synonym_graph` search analyser with two multi-word, multilingual trait entries, against five
hand-written passages:

| query | retrieved | via |
|---|---|---|
| `diet` | "stomach contents of three specimens" · "feeds on the leaves" · "Die **Nahrung** besteht" | English paraphrase **and** cross-language |
| `Nahrung` | the two English passages, plus the German one | reverse direction works |
| `food plant` | "Larval **host plant** recorded as *Senna*" | multi-word synonym |
| `wirtspflanze` | same | cross-language multi-word |

An irrelevant passage mentioning *Muscidae* material scored no hit in any of the four. So the
mechanism does what §3a needs — paraphrase and cross-language recall at **zero index cost**, editable
without reindexing because it is a *search* analyser. What it cannot do is generalise beyond the
thesaurus, which is exactly the gap step 3 closes.

### Relevance measured — and reranking makes results *worse*

The experiment this whole section was waiting on. **Result: BM25 + the trait thesaurus wins, and
adding a cross-encoder reranker degrades it.** That reverses the §3a recommendation.

**Setup.** 300 BHL-Light items → 77,678 pages → **242,815 passages** (~110 words each) indexed in
ES 8.15 with the `folding` analyser and a 13-entry multilingual trait `synonym_graph` as *search*
analyser. 8 trait/ecology queries. Top-10 from each system pooled, deduplicated, shuffled and
graded blind to system: **2** = states the trait, **1** = on-topic but no statement, **0** =
irrelevant. 189 passages judged (128 × grade 2, 43 × grade 1, 18 × grade 0).

| system | P@10 (grade 2) | any-rel@10 | **nDCG@10** |
|---|---|---|---|
| BM25, `folding` only | 0.650 | 0.900 | 0.757 |
| **+ trait synonyms** | **0.762** | **0.963** | **0.868** |
| + rerank, `bge-reranker-base` | 0.700 | 0.875 | 0.760 |
| + rerank, `bge-reranker-v2-m3` | 0.700 | 0.925 | 0.811 |
| RRF(synonyms, base) | 0.700 | 0.875 | 0.796 |
| RRF(synonyms, v2-m3) | 0.700 | 0.925 | 0.836 |

Four ways of applying the reranker, including rank fusion that preserves the BM25 order, and **none
beats synonyms alone.**

### Why: OCR noise inverts the cross-encoder

Not a subtle effect. On q1 (*"what does it feed on, diet and food habits"*) the reranker **promoted
into the top 10**:

- an **acknowledgements page** (grade 0)
- a bibliography of **ethnographic films** about aboriginal food habits (grade 0)
- a reference list on **teeth of Australian Aborigines** (grade 0)

and **demoted out of it** four passages that state diets outright — sipunculan larvae fed on algal
cultures, *Birgus latro* digesting chitin from crab skeletons, *H. virgata* not eating green plants,
a beetle feeding inside caves.

The mechanism: **the cleanest, most fluent text in a BHL page image is front matter — titles,
reference lists, acknowledgements — while the passages that actually state a trait are mangled**
(`"IH, pirgata does so in one year"`, `"Hlairi once came out at night"`). A cross-encoder trained on
clean prose scores fluent-and-topical above garbled-and-factual. BM25 doesn't care: a term match is
a term match.

**This is an artefact of the OCR, not of the model** — which makes it a concrete, testable
prediction: *if the NHM Mistral 3 pass happens and the text gets clean, re-run this and reranking
may well flip to positive.* On current IA OCR it is a net negative and should not be deployed.

### Where reranking did help — and what to invest in instead

| query | BM25 | + synonyms | + v2-m3 |
|---|---|---|---|
| q6 insects that pollinate the flowers | 0.777 | 0.777 | **0.948** |
| q7 *Nahrung der Raupe* (German) | 0.270 | **0.588** | **0.698** |
| q1 diet and food habits | 0.788 | **0.815** | 0.582 |
| q2 larval host plant | 0.378 | **0.838** | 0.670 |

Reranking helped on 2 of 8 queries and hurt on 5. The pattern is consistent: **it helps where lexical
matching fails (cross-language, conceptual) and hurts where lexical matching already works.**
Selecting between them per query would need to know which case you are in, which you don't — though
the spread of BM25 scores is a plausible untested signal.

**The thesaurus is where the measured gain is.** It lifted nDCG@10 by **+0.111** overall, and on the
German query by **+0.318** (0.270 → 0.588) — more than doubling it. It costs nothing at index time,
is editable without reindexing, and is fully inspectable.

To be clear about provenance: **those 13 rules were written by Claude as a test fixture**, in a few
minutes, not drawn from any published vocabulary and not authoritative. That is the finding, not a
caveat to it — *thirteen invented rules beat a 568M-parameter neural reranker*. A properly curated
multilingual trait thesaurus is the highest-return work in this entire document.

The rules, a working index definition, the 240 graded passages and a harness to re-measure after
every edit are in **[`trait-search/`](trait-search/)**. Two mechanics verified there and easy to get
wrong: synonyms must sit in the **search** analyser (or every edit becomes a 63.9M-document
reindex), and `synonym_graph` must come **after** `asciifolding` — with the wrong order the
*unaccented* form silently stops expanding, which is exactly what BHL's diacritic-dropping OCR
produces.

### Caveats, stated plainly

- **Ceiling effect.** 68% of the judged pool is grade 2 — this corpus is so dense in natural-history
  content that BM25+synonyms already returns mostly-relevant results, leaving little headroom above
  0.868. On a sparser corpus the ranking could differ.
- **8 queries, one assessor** (me), blind to system but not to the task. Small and not independent.
- **Pooled from the systems' own top-10s**, so the ideal DCG is system-biased in the usual way.
- **The reranker could only reorder**, never add. This measures precision, not recall — see below.

### What this changes

1. **Drop step 3 from §3a.** The stack is passages + synonyms. No reranker, no inference at query
   time, no model to host, and query latency returns to plain Elasticsearch milliseconds. The
   reranker-latency work above becomes moot for deployment — keep it only as the cost model for
   *if* clean text later makes reranking worth revisiting.
2. **The case against a corpus-wide dense text index gets stronger.** A cross-encoder is strictly
   more powerful at ranking than a bi-encoder, and it could not beat BM25 here because of OCR noise.
   A dense bi-encoder reads the same garbled text and is unlikely to fare better. **Caveat that
   matters:** this experiment cannot test *recall* — dense retrieval might still surface passages
   that lexical search never proposes. That question is still open and is the one remaining argument
   for embeddings.
3. **The machine decision is reinforced.** 128 GB was already right; the reranker's ~1 GB and its
   CPU load now drop out too. Hard residency stays 78 GB (image vectors).
4. **Mistral 3 matters for a new reason.** Not just figure regions and the n-gram decision — clean
   text is the precondition for *any* neural ranking to work on this corpus. Re-run this experiment
   as the first thing after a Mistral pass.

### Recall measured — dense retrieval *does* add something, and it is not free

The one question the reranking experiment could not answer: does dense retrieval surface relevant
passages that lexical search never proposes at all?

**Setup.** Same **242,815 passages**, written once to a canonical file so both indexes cover provably
identical text. Embedded with `intfloat/multilingual-e5-base` (768-d, mean-pooled, `query:`/
`passage:` prefixes) — 85 min on this M1's GPU. **Exact brute-force kNN**, so no ANN recall
confound. The lexical run goes **200 deep**, so "lexical missed it" means genuinely outside a
realistic candidate pool. Dense top-10 minus lexical top-200, judged blind on the same 0/1/2 scale.

| query | dense-only | grade 2 | grade 1 | grade 0 |
|---|---|---|---|---|
| q1 diet and food habits | 4 | 1 | 1 | 2 |
| q2 larval host plant | 5 | 2 | 2 | 1 |
| q3 found in the stomach | 6 | **5** | 0 | 1 |
| q4 breeding season | 8 | **7** | 1 | 0 |
| q5 parasite on host | 7 | **6** | 1 | 0 |
| q6 insects that pollinate | 5 | **4** | 1 | 0 |
| **q7 *Nahrung der Raupe*** | **10** | **0** | 4 | 6 |
| q8 habitat, humid forest | 6 | **5** | 1 | 0 |
| **total** | **51 of 80 (64%)** | **30** | 11 | 10 |

**Dense adds real recall: 3.8 genuinely relevant passages per query that lexical never surfaced**,
including *Etiella behrii*'s host-plant table, King George whiting diet, wombat parasite-site tables
and frog breeding-chorus records. 64% of the dense top-10 lies outside lexical's top-200, and 59% of
that is grade 2.

So the earlier "cut the embeddings" conclusion was **precision-only and is hereby qualified**:
lexical+synonyms still wins the top-10 (P@10 0.762 vs 0.59 on dense's unique portion), but dense is
genuinely *additive* rather than merely a reshuffle.

**The cross-language result is the opposite of the expectation.** q7 was supposed to be dense's
strongest case and is its worst: 10 of 10 dense hits were outside lexical's reach and **none was
relevant**. It retrieved German passages about termite decomposition and scorpion husbandry —
correct language, roughly the right topic (feeding), wrong fact entirely. The synonym thesaurus
handled that query far better (0.270 → 0.588). **Dense matched language and topic but not the
trait.**

**Model substitution, flagged in advance.** The brief specified BGE-M3; that needed ~6 h here against
85 min for e5-base, which is smaller. This makes the result *robust in the favourable direction* — a
stronger model would find at least as much — so the recall finding stands. Also note the kNN was
exact; at 60 M vectors HNSW would lose some of this, making 3.8/query an **upper bound**.

**Caveat on abundance.** 68% of the earlier judged pool was grade 2 — this corpus is saturated with
natural-history content, so "found more relevant passages" partly reflects that relevant passages
are everywhere. On a corpus where the answer is rare, the contribution could differ in either
direction.

### What the recall result does and does not change

**It does not change the machine.** Capturing this corpus-wide is what costs: at page level, 60 M ×
768-d halfvec is **107 GB resident**, and at passage level (~300 M) it is far beyond any single box.
Neither fits 128 GB alongside the 78 GB image index, and 256 GB is unavailable on AM5 anyway. So:

| way to capture the recall | cost | verdict |
|---|---|---|
| corpus-wide passage index | ≫256 GB | out |
| corpus-wide page index, 768-d | 107 GB resident | doesn't fit with image vectors |
| **trait-flagged subset** (brief's own proposal, ~10% of pages) | **~14 GB** | **fits trivially** |
| pgvectorscale/DiskANN + SBQ on text | untested | the one unexplored lever |

**The recommendation therefore stands, with one addition:** build the Elasticsearch passage +
synonym system first, because it wins on precision and costs nothing extra. Then add a **targeted**
dense index over trait-flagged passages to recover the recall — 14 GB, not 107 GB. That is the
brief's own compromise, and it is now supported by measurement rather than assumed.

### What this does to the machine

Dropping the dense text layer removes 135 GB from the residency requirement and 176 GB from disk:

```
hard residency requirement:  78 GB   (image HNSW only)
wants page cache:           ~440 GB  (ES pages + passages, degrades gracefully)
memory-mapped:            22-45 GB   (QLever)
reranker model:              ~1 GB
```

**So 128 GB becomes viable again.** The forcing argument for 256 GB was entirely the dense text
index; without it, 256 GB buys Elasticsearch page cache — a performance choice rather than a
feasibility one. Given that ES would hold ~440 GB across two indexes and only ~40 GB of a 128 GB
box would be free for caching it, 256 GB is still the better buy if the budget is there. But it is
no longer the difference between working and not working, and that changes the risk on the purchase.

### What still argues for dense embeddings

Be fair to the option: cross-encoders rerank, they don't retrieve. If a relevant passage shares
*no* lexical overlap with the query in any language — the pure-paraphrase case, or a query in a
language the passage isn't written in — step 2 won't surface it as a candidate and step 3 never
sees it. Dense retrieval is genuinely better at that. The question is how often it happens in
practice on real trait queries, which is measurable:

**run step 1+2+3 and a small dense baseline over the same 20–30 queries on a few journals, and
count the passages dense found that lexical+rerank missed entirely.** If that number is small, the
135 GB is unjustifiable. If it's large, embed only the trait-flagged subset per the brief, not the
whole corpus.

## The crunch, and the machine

```
image HNSW   78 GB
text HNSW   135 GB
            ------
            213 GB   of index that wants to be resident
          + ES page cache (share of 208 GB)
          + PG shared_buffers, RDF working set, OS
```

HNSW makes hundreds to thousands of random hops per query. RAM random-read is ~0.1 µs, NVMe
~50–100 µs — 500–1000× worse. Recall doesn't degrade when the index spills, *latency* does, from
milliseconds to seconds. So the index must be resident, and that is what sizes the box.

**128 GB cannot hold both vector indexes at full precision. 256 GB can.** That was the study's
headline finding — but it is **conditional on keeping the dense text index**, and §3a argues that
index is the thing to cut. The honest position is therefore two-branched:

| architecture | hard residency | verdict on 128 GB |
|---|---|---|
| image HNSW + dense text HNSW | **213 GB** | **not viable** |
| image HNSW + Elasticsearch passages + reranker (§3a) | **78 GB** | **viable** |

So the machine decision now follows the retrieval decision, not the other way round:

1. **If the §3a stack works — buy 128 GB**, with the caveat that Elasticsearch would hold ~440 GB
   across the pages and passages indexes and only ~40 GB would be spare for page cache. It will
   work; it will not be fast on cold queries.
2. **Buy 256 GB anyway if the budget allows.** With the dense layer gone, the extra 128 GB goes
   entirely to Elasticsearch page cache, which is where the latency now lives. This is a
   performance purchase, not a feasibility one — a much more comfortable thing to be spending on.
3. **Buy 256 GB and keep the dense text option open** only if the §3a evaluation shows lexical +
   rerank missing a material number of relevant passages.

Either way the brief's DIMM advice matters: 2 × 64 GB, not 4 × 32 GB, and check the board maximum.
The upgrade path is the plan's escape hatch, and it now protects against a *retrieval* finding
rather than against having mis-sized an index.

### The quoted machine (PCSpecialist 3477802) — assessed

| | quoted |
|---|---|
| CPU | **Ryzen 9 9950X**, 16C/32T Zen 5, 80 MB cache |
| Board | ASUS PRIME X870-P WIFI (AM5) |
| RAM | **128 GB Kingston DDR5-5600, 2 × 64 GB** |
| Storage | 4 TB Samsung 9100 PRO (PCIe 5.0) + 4 TB Samsung 990 PRO (PCIe 4.0) |
| GPU | integrated only |
| PSU / cooling | Corsair CX-550 / PCS FrostFlow 200 (rated 250 W) |
| Price | **£3,645 ex VAT / £4,374 inc VAT**, 3-year warranty |

**The build matches the analysis closely.** 2 × 64 GB is the correct RAM choice — full dual-channel
at 5600, two slots free. 8 TB across two NVMe drives matches the 0.9–1.8 TB live estimate with room
for alias-swap reindexing and QLever's ~85 GB build peak. No GPU is right (§GPU). Sixteen Zen 5
cores with full-width AVX-512 and VNNI sit at the *optimistic* end of the 4–8× reranker projection.

### 128 GB vs 256 GB: stay at 128, because 256 is not really on offer

**The 9950X is officially rated at 192 GB maximum.** 4 × 64 GB = 256 GB exceeds the CPU's supported
capacity, and 4-DIMM DDR5 on AM5 is a stability lottery that typically drops to ~5200 MT/s. The real
ceiling here is 192 GB via 4 × 48 GB — which means *discarding* the quoted 2 × 64 GB sticks, at
reduced speed.

So the upgrade path I described earlier is weaker than I implied on this platform, and that turns a
vague "256 GB if budget allows" into a concrete architectural commitment:

| architecture | resident RAM | on this machine |
|---|---|---|
| §3a: ES passages + synonyms + reranker | **78 GB** | **fits comfortably in 128 GB** |
| corpus-wide dense text index | 213 GB | **not achievable** — would need Threadripper/EPYC |

**Update after the relevance experiment: this concern is resolved, and the machine is right.** The
only thing that wanted 256 GB was the corpus-wide dense text index, and that has now been cut on
measured evidence (*Relevance measured*). Residency is 78 GB. 128 GB is not a compromise here, it is
correct with headroom, and the 192 GB ceiling never binds.

The concern would only return if the outstanding **recall** question came back strongly positive
*and* the answer required embedding the whole corpus. Even then the brief's own trait-flagged subset
— 10% of pages, ~6 M vectors, **14 GB** — fits trivially. It takes a corpus-wide dense index to need
256 GB, and that is now the hardest thing in this document to justify.

It is not a dead end if dense embeddings turn out to be needed. What 128 GB rules out is a
*corpus-wide* dense index, not embeddings as such:

- Embed only the brief's trait-flagged paragraph subset — at 10% of pages that is ~6 M vectors,
  **14 GB** at 1024-d halfvec. Fits trivially.
- pgvectorscale/DiskANN with SBQ on the text vectors. Untested on BGE-M3, and the image-vector
  rejection does not transfer to text-to-text geometry.

### Three things to change or query before ordering

1. **Cooling.** FrostFlow 200 is rated to 250 W against the 9950X's ~200 W PPT — only 25% headroom,
   and sustained all-core AVX-512 is this machine's *defining* workload (multi-hour index builds,
   batch inference), not an occasional peak. Confirm the radiator size and consider a 240/280 mm AIO
   or an NH-D15-class air cooler. Throttling only makes builds longer, but it is cheap now and a
   teardown later.
2. **PSU.** 550 W is adequate for a no-GPU build (~300–350 W peak). The CX series is entry-tier
   though, and this box runs 24/7 — an RM/HX or Seasonic equivalent is a small upcharge for better
   ripple and longevity.
3. **No ECC.** The brief itself noted that a workstation platform with ECC is more robust for long
   builds. That has been traded away, which is defensible at this price, and is mitigated by
   everything here being derived data that can be rebuilt. Worth accepting knowingly rather than by
   omission.

**Drive allocation:** put Elasticsearch and QLever on the 9100 PRO (sequential-write-heavy segment
merging and index builds benefit from PCIe 5.0), and Postgres vectors on the 990 PRO (HNSW serving
is random-read-latency bound, where the two drives are near-identical).

### Buy vs rent, per the brief's request

Home power at 150 W continuous ≈ **£30/month** (£355/year at ~27p/kWh).

| compared against | net saving/month | break-even (inc VAT) | (ex VAT) |
|---|---|---|---|
| Hetzner AX-class 128 GB as-listed | £77 | **4.7 years** | 3.9 years |
| same, with disks upgraded toward 8 TB | £102 | **3.6 years** | 3.0 years |

**Payback is 3–5 years, i.e. at or beyond the 3-year warranty.** The purchase is not obviously
cheaper than renting on a 3-year horizon. What justifies it is control, no egress metering, and that
8 TB of fast NVMe is disproportionately expensive to rent — not raw cost. Worth being clear-eyed
about, since the brief asked for the number.

### Disk

| component | low | high |
|---|---|---|
| Elasticsearch, no n-gram | 239 GB | 239 GB |
| Elasticsearch n-gram subfield (optional) | — | 330 GB |
| Image vectors + metadata, pages | 125 GB | 125 GB |
| Image vectors, + figure crops | — | 108 GB |
| Text vectors + metadata (1024-d) | 176 GB | 176 GB |
| **RDF (QLever, geometry included)** | **22 GB** | **45 GB** |
| Side tables / captions | 20 GB | 60 GB |
| Mistral 3 page output (structured text) | 154 GB | 247 GB |
| Scratch, rebuild, index staging | 200 GB | 500 GB |
| **total live** | **0.9 TB** | **1.8 TB** |

**8 TB NVMe is still the right buy**, but note *what* drives it: Elasticsearch, the vector tables
and the incoming Mistral output. QLever is 22–45 GB — under 3% of the total. The brief's 1.5–3 TB
guess turns out about right once the Mistral text is stored, though for different reasons than
assumed.

Two drives, Elasticsearch on one and Postgres on the other. The headroom pays for alias-swap
reindexing, keeping raw vectors beside the live index, and QLever's ~85 GB build peak.

### Reranker latency, measured — and my estimate was wrong

Measured on this M1 (4 performance + 4 efficiency cores, macOS 14.7, torch 2.14), CPU-only, against
400 real ~110-word passages built from the BHL page slice. Query: *"What does this species feed on?
diet, prey, stomach contents"*.

| model | params | runtime | ms/passage | 50 candidates | 100 |
|---|---|---|---|---|---|
| `bge-reranker-base` | 278 M | torch fp32 | 49 | **2.5 s** | 5.0 s |
| `bge-reranker-base` | 278 M | ONNX fp32 | 110 | 5.5 s | 11.1 s |
| `bge-reranker-base` | 278 M | **ONNX int8** | **40** | **2.0 s** | 3.9 s |
| `bge-reranker-base` | 278 M | torch fp32 on MPS | 23 | 1.1 s | 2.3 s |
| **`bge-reranker-v2-m3`** | **568 M** | torch fp32 | **175** | **8.7 s** | 17.5 s |

**My §3a estimate of ~0.5–1.5 s for 50–100 candidates was optimistic by 2–4× on this hardware.**
Correcting it rather than restating it.

What the numbers actually say:

- **`ms/passage` is flat in batch size** (40–50 ms across N = 25→200), so cost is linear in candidate
  count. The candidate-pool size is the main lever available at query time.
- **Sequence length matters nearly as much:** `max_length` 128 / 192 / 256 gave 34 / 50 / 66 ms per
  passage. Passages at ~110 words fit comfortably in 192, so cap it there — going to 256 "just in
  case" costs 30%.
- **int8 is worth it, but only via ONNX Runtime.** torch's dynamic quantisation is unavailable on
  macOS arm64 (`NoQEngine` — no FBGEMM/QNNPACK in that build); it works on x86 Linux, which is what
  the target machine would be. ONNX int8 gave 2.8× over ONNX fp32 and beat torch fp32 by 1.25×.
- **`v2-m3` is 3.6× slower than base, not the ~2× I assumed.** At 8.7 s for 50 candidates it is
  unusable at query time on a CPU of this class. Naming it in §3a was a mistake.
- **MPS gave only 2.2×.** Worth noting for the GPU question: on integrated graphics a GPU is not a
  transformative win. A discrete GPU would be, which matters below.

### The unresolved problem: base may not be good enough

A smoke test on 120 random passages, of which only 2 contained any feeding term at all:

| model | ranks it gave those 2 passages |
|---|---|
| `bge-reranker-base` | 22 and 32 |
| `bge-reranker-v2-m3` | **0 and 1** |

**This is a weak signal and must not be read as an evaluation** — the passage pool is random BHL
text with almost no genuine trait content, and on inspection both "feeding" matches are citations in
reference lists rather than trait statements. But it points the wrong way for cost: the model that is
3.6× more expensive is also the one that behaved sensibly, and the cheap model I recommended ranked
the only lexically relevant passages 22nd and 32nd.

So the honest position is that **model choice is unresolved, it swings latency by 3.6×, and the one
piece of evidence available leans toward the expensive option.**

### Projection to the target machine, and what follows

Extrapolating from M1's 4 performance cores to a modern x86 workstation (16–32 cores, AVX-512 **VNNI**
for int8 dot products) suggests roughly 4–8×:

| | projected ms/passage | 50 candidates |
|---|---|---|
| base, ONNX int8 | 5–10 | **0.25–0.5 s** |
| `v2-m3`, ONNX int8 | 20–35 | **1.0–1.75 s** |

**This is extrapolation, not measurement** — flagged as such, and the single most useful thing to
re-measure once the machine exists. On those figures both models are viable, the base model
comfortably and `v2-m3` acceptably for a scholarly tool.

Consequences for the plan:

1. **Cap the candidate pool at 50**, not 200. It is the cheapest lever and it is linear.
2. **Cap `max_length` at 192.** Free 30%.
3. **Deploy through ONNX Runtime int8, not torch.** On x86 with VNNI this is the single biggest win.
4. **Budget for `v2-m3`, hope for base.** Size the CPU assuming ~1–1.75 s per query; if the relevance
   work shows base is sufficient, that becomes ~0.3 s and the headroom goes elsewhere.
5. **Cache reranked results.** Trait queries repeat across users, and the cache is keyed on
   (query, candidate set).
6. **The GPU-free decision holds. Buy no GPU.** Reranking is the only workload in the whole design
   that could conceivably want one, and the arithmetic says it doesn't. Taking the *expensive* model
   on the target machine — `v2-m3` at ~143 ms/passage under ONNX int8, scaled by 4–8× for a 16-core
   x86 with VNNI:

   | | 50 candidates | 25 candidates |
   |---|---|---|
   | `v2-m3`, conservative 4× | 1.8 s | **0.9 s** |
   | `v2-m3`, optimistic 8× | 0.9 s | 0.45 s |
   | base model, 4× | 0.5 s | 0.25 s |

   Even the pessimistic corner is 1.8 s, and halving the candidate pool puts it under a second. A GPU
   would only matter if the target were sub-200 ms, which it is not for a scholarly search tool.

   For a GPU to be justified, **all four** of these would have to hold: base is insufficient, *and*
   the CPU delivers less than 4× over an M1's performance cores, *and* ~2 s/query is unacceptable,
   *and* capping candidates and caching fail to recover it. Each of the last three has a cheap
   remedy, and synonyms-only (validated above, zero inference cost) is the floor. **Treat "no GPU" as
   settled and do not reopen it on latency grounds alone.**

**The experiment that should now precede committing to §3a** is not more latency measurement, it is
relevance: assemble 20–30 real trait queries and a passage pool that actually contains trait
statements, judge the top 10, and compare BM25+synonyms alone, +base reranker, and +`v2-m3`. That
decides both the architecture and the CPU budget, and nothing else in this document is blocked on it.

### GPU — Elasticsearch cannot use one, and the plan doesn't need one

Worth stating flatly, because an earlier draft of this document mentioned a GPU next to
Elasticsearch and that was misleading:

1. **Elasticsearch search itself has no GPU path and never has.** BM25, aggregations, highlighting
   and phrase matching are CPU, page cache and IO. Even ES's own `dense_vector` HNSW is CPU — it
   uses the Panama Vector API for SIMD, not a GPU.
2. **Elasticsearch's ML inference is CPU-only by design.** ES runs models through native libtorch on
   CPU. The enhancement request asking for GPU-accelerated ML
   ([elastic/elasticsearch#61690](https://github.com/elastic/elasticsearch/issues/61690)) is
   **closed as not planned**. So if the reranker or any learned-sparse model runs *inside* ES, a GPU
   is not merely unnecessary — it cannot be used.
3. **Bulk embedding and any OCR/layout pass are the real GPU jobs, and they are rentals.** One-off,
   in-region on AWS Spot, $25–50 for the image embeddings. Nothing to buy.
4. **Query-time reranking is the only place a GPU would help**, and only if run outside ES.

So the instinct to sidestep GPUs entirely is sound. The home machine does not want one. What that
decision *does* constrain is the reranker, which is the one CPU-hungry piece in §3a:

- `bge-reranker-v2-m3` is XLM-RoBERTa-**large** (~560 M params). Scoring 100 passages per query on
  8 CPU cores is plausibly several seconds — too slow to leave unoptimised.
- The levers, roughly multiplicative: a **base**-size multilingual reranker (~278 M) instead of
  large; **int8 ONNX Runtime or OpenVINO** quantisation (typically 2–4× on CPU); **50 candidates**
  rather than 200; and passages of ~150 tokens rather than 512, since cost scales with length.
- Applied together those should land inside ~0.5–1.5 s, which is fine for a scholarly search tool
  even though it is not Google-fast. **This needs measuring, not assuming** — it is the one number
  in §3a I have not verified, and it is the difference between the recommended stack being
  interactive and merely usable.
- If it proves too slow: rent a GPU for the reranker as a separate small service, or fall back to
  step 2 alone (synonyms, no reranking), which is already validated above and costs nothing.

None of this argues for buying a GPU. It argues for measuring reranker latency before committing to
the architecture.

## 4. RDF — QLever, with geometry in, and the name table as the real question

Exact predicate census for the current 771 M-triple graph, from `bhl://schema`:

| layer | triples | share |
|---|---|---|
| **names** (`dwc:scientificName` 210.7 M + `scientificNameID` 151.8 M + `nameBankID` 61.0 M) | **423 M** | **55%** |
| page structure (`rdf:type`, `sequenceOrder`, `isPartOf`, `pageNumber`, `pagePrefix`, `date`) | 326 M | 42% |
| bibliographic + creators + `PagePosition` reification | 22 M | 3% |

**"Pages with names will probably dominate" — confirmed, and not close.** `dwc:scientificNameID`
alone (151.8 M) outweighs every bibliographic triple combined by a factor of 15.

### QLever is the cheapest component, not the reason for big drives

Two published reference points, which bracket BHL:

| dataset | triples | input | final index | bytes/triple |
|---|---|---|---|---|
| DBLP | 390 M | 1.8 GB gz | **8 GB** | 22 |
| Wikidata | ~20 B | ~100 GB gz | **992 GB** | 53 |

Bytes-per-triple is driven by **vocabulary size**, not triple count — Wikidata has billions of
distinct IRIs and literals; BHL has 28 predicates, ~65 M IRIs and a name vocabulary that repeats
heavily. So BHL sits nearer the DBLP end. At 25–50 B/triple:

| graph | triples | QLever index | peak during build |
|---|---|---|---|
| current, as served | 771 M | 18–36 GB | ~70 GB |
| **+ geometry (2/page)** | **899 M** | **21–42 GB** | **~85 GB** |
| + geometry (4/page) | 1.03 B | 24–48 GB | ~95 GB |
| geometry, names dropped | 475 M | 11–22 GB | ~45 GB |

Build headroom: QLever's own Wikidata guidance is 2 TB of disk for a 992 GB index, i.e. **~2× the
final size** for the external sort and the six permutations. Applied here that is **~85 GB peak for
a ~900 M-triple BHL graph.**

So the impression that QLever motivates large drives comes from reading Wikidata-scale guidance.
**BHL is 4.5% of Wikidata.** Even taking Wikidata's own 53 B/triple wholesale gives a 45 GB index.
QLever will be the smallest of the four services by roughly an order of magnitude — Elasticsearch
alone is 5–10× larger. Buy 8 TB for Elasticsearch, the vector tables and the Mistral output; QLever
rides along free. Its RAM appetite is modest too (memory-mapped index, RAM for query processing),
so unlike the vector indexes it does not compete for the 256 GB.

### Page geometry: include it, it's noise-level cost

The brief ruled geometry out on the grounds that 62 M pages × 3–4 triples "would be 200M+ triples
and would dominate the store." The triple count is right; **the conclusion doesn't hold at QLever's
compression.** Width and height for every page is **128 M triples = +17% on the graph and 3–6 GB of
index.** For something IIIF and W3C annotation targets structurally require, that is not a
trade-off worth making. Include it, and drop the side table — one less thing to keep in sync.

(If it ever *did* need to shrink: `bhlv:pagePrefix` is 52.3 M triples of presentational string and
is the first thing to go.)

### The name×page table: decide on precision, not on size

This is genuinely undecided, and the sizing answer is that **size should not decide it.** The whole
423 M-triple name layer is 10–20 GB of QLever index. Dropping it saves less disk than one
Elasticsearch replica shard.

What the census does offer is a middle path, because the layer is not homogeneous:

| | triples | character |
|---|---|---|
| `dwc:scientificName` | 210.7 M | raw name-finder strings, includes the noise |
| `dwc:scientificNameID` | 151.8 M | **reconciled** to Wikidata — the precision-filtered subset |
| `bhlv:nameBankID` | 61.0 M | uBio legacy, largely redundant with the above |

So rather than all-or-nothing: **keep name assertions that reconciled to an identifier, drop the
unreconciled strings and the uBio ids.** That is ~300 M triples instead of 423 M, and the filter is
precision rather than volume — it removes exactly the noise you object to, and the 59 M-triple gap
between `scientificName` and `scientificNameID` is a reasonable first estimate of how much of the
layer is unreconcilable junk.

The stronger argument for waiting: **if the Mistral 3 run happens, the name layer should be
re-derived from the better text anyway.** Loading 423 M triples of ABBYY-era name-finder output now
means loading something you intend to replace. Keep the name×page table out of the initial QLever
index, ship the graph with geometry and bibliographic structure, and add names back as a separate
graph once there is cleaner text to find them in — with provenance recording which text pass and
which name-finder produced them, so the next re-derivation is a swap rather than a migration.

## Summary table (the brief's Section "Suggested method", filled in)

| Component | Unit count | Disk | RAM for good performance | Build time | Notes |
|---|---|---|---|---|---|
| Elasticsearch | 63.9 M pages | **194 GB measured** (489 GB w/ n-gram) | 31 GB heap + as much page cache as spare | hours–1 day | measured on 8,269 pages; `names` subfield only 8 GB; offsets +23% |
| Image vectors | 60 M pages (+18–52 M figures) | 125–233 GB | **78 GB resident** (146 GB with figures) | **HNSW ~2–4 h measured-and-extrapolated**; $25–50 AWS Spot (embed) | ViT-B/32 512-d halfvec; `ef_search=300`; binary quant **rejected**; needs `/dev/shm` ≥ 100 GB + `maintenance_work_mem` 80 GB or it is ~5× slower |
| ES passages index (§3a) | 200–360 M passages | 231–248 GB | page cache; **no residency requirement** | hours–1 day | the recommended route for trait search; fixes dilution cheaply |
| Cross-encoder reranker | 50 candidates/query | ~1 GB model | ~1 GB, no index | none | **measured** 40 ms/psg (base, ONNX int8, M1) → ~0.25–0.5 s/query projected on target x86; `v2-m3` 3.6× slower |
| Text vectors *(now optional)* | 60 M pages | 176 GB | **135 GB resident** | hours + one GPU pass | BGE-M3 1024-d; **candidate to cut** — see §3a |
| RDF store (QLever) | 899 M triples w/ geometry | **22–45 GB** | modest; memory-mapped, doesn't compete | ~hours, ~85 GB peak disk | geometry **in**; name layer deferred pending cleaner text |
| Geospatial (`geo_point`) | ~955 K points / 328 K pages | **~29 MB** | negligible | minutes | 0.51% of pages carry a coordinate; `geotile_grid` 2 ms |
| Side tables | 63.9 M rows | 20–60 GB | in PG shared_buffers | minutes | captions, caption FTS (geometry now in RDF) |
| Mistral 3 output | 63.9 M pages | 154–247 GB | — | NHM's cost, not ours | input to everything downstream |
| **Total, §3a stack** | | **0.9–1.7 TB live → buy 8 TB** | **78 GB resident → 128 GB viable, 256 GB better** | | one box is sufficient |
| **Total, with dense text** | | **1.1–1.9 TB live → buy 8 TB** | **213 GB resident → 256 GB required** | | one box is sufficient |

**One box or several: one**, on either branch. Nothing here needs distribution, and splitting across
two 128 GB boxes would duplicate the OS, the Postgres instance and the operational burden to solve
a problem that 256 GB in one chassis solves more cheaply.

## What is actually worth benchmarking now

Storage is settled by arithmetic. These are not:

1. **~~Does the embedding layer beat BM25 + synonyms?~~ — done, both halves.** Precision: no
   (*Relevance measured*). Recall: **yes, 3.8 unique relevant passages per query** (*Recall
   measured*). Conclusion: lexical+synonyms for ranking, plus a **trait-flagged dense subset
   (~14 GB)** for recall — not a corpus-wide index. The remaining unexplored lever is
   pgvectorscale/DiskANN + SBQ on *text* vectors, which the CLIP-based rejection does not cover.
2. **SBQ/DiskANN recall on BGE-M3 text vectors.** The only thing that would rescue 128 GB. Mirror
   `hetzner/bq_recall_eval.py` with text-to-text queries.
3. **~~HNSW build time at 60 M~~ — measured.** Scaling series to 2M rows on pgvector 0.8.6 with
   anisotropy-matched vectors fits **t = 1.61e-5 · n^1.170** (within 3% on every point), projecting
   **~2–4 h on the 9950X with the graph resident**. The brief's "hours to days" resolves to the
   low end — *conditionally*: at ~10% of graph size the build measured **5.07× slower**, so the
   repo's `maintenance_work_mem=8GB` against a 78 GB graph would mean ~13–20 h. `/dev/shm` must
   also be raised above its 50%-of-RAM default. See `INDEXING-NOTES.md` items 22 and 22a.
4. **ES page-cache behaviour at 208 GB on a 256 GB box while Postgres holds 213 GB.** This is the
   real contention question and the one number that would still justify 512 GB.
5. **Figure crops per page on an unbiased sample.** 0.87/page comes from 22 figure-rich items. The
   Mistral 3 output will settle it for free; until then treat 0.3–0.87 as the planning range.
6. **~~Reranker latency~~ — done.** 40 ms/passage (base, ONNX int8) / 175 ms/passage (`v2-m3`) on
   M1 CPU; see *Reranker latency*. **Replaced by: reranker *relevance* on real trait queries**,
   which now decides both the model and the CPU budget. 20–30 judged queries against a passage pool
   that actually contains trait statements, comparing BM25+synonyms, +base, +`v2-m3`.
7. **Re-run the agg-vs-collapse comparison at 10 M+ docs.** It was measured at 496 K, where the
   terms agg wins by 10–30×. Collapse's cost is fixed per group while the agg's grows with matching
   docs, so the ranking could invert at corpus scale.
8. **Mistral 3 text vs IA OCR on the same queries.** This decides the 330 GB n-gram subfield, and
   it also shifts the §1 experiment above — cleaner text helps embeddings more than it helps BM25,
   because fuzziness already compensates for OCR noise on the lexical side.

### The figure-level prerequisite, now assumed solved

Earlier drafts made figure-level image search contingent on a layout pass we would have to fund:
Datalab's API at historical ~$3/1000 pages is ~$190 K for 63.9 M pages, and self-hosted Surya was
the ~$200–400 workaround. **Both are moot if the NHM London Mistral 3 run happens over the whole
corpus.** Assuming it does, it changes four things:

1. **Figure regions and captions arrive for all 63.9 M pages at no cost to us.** Figure-level image
   search stops being gated on infrastructure we have to build. The 18–52 M crop range above
   becomes actionable, and the bounding boxes are what the geometry triples (§4) are for.
2. **The OCR text improves substantially**, which cuts *against* spending on the n-gram subfield.
   The brief's query-time fuzziness and the `folding` analyser exist to paper over ABBYY/DjVu
   noise. Cleaner transcription is the better fix, and it is free here — so treat the 330 GB
   n-gram decision as deferred until the Mistral text can be measured against the IA text on the
   same queries.
3. **Paragraph boundaries and reading order become known**, which makes paragraph-level chunking
   *possible*. It does not make it affordable: ~300 M vectors is still ~0.7 TB of index. The
   brief's trait-flagged-paragraph compromise remains the right shape.
4. **The name layer can be re-derived far more cleanly** — which is the crux of the name×page
   decision in §4.

Two things to pin down with NHM rather than assume: the **output schema** (block labels, bounding
boxes, reading order, and in what coordinate space), and whether page **width and height** come
with it, since those feed both IIIF and the geometry triples. Also budget for storing their
output: structured markdown for 63.9 M pages is ~150–250 GB, and it becomes the input to
everything downstream, so it wants to live on the box next to the indexes.

## Reproducing the BHL-Light measurements

```bash
php -d memory_limit=2G future/extract-pages.php future/pages.jsonl
```

One JSON document per page (text, names, image key, dimensions), so every experiment reads the
same corpus. Measured **2,494 pages/s** → full BHL-Light in ~15 min, ~5.4 GB JSONL
(2,391 B/page). Resumable and idempotent per the brief's conventions.
