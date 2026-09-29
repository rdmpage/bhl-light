# Trait search for BHL: the synonym thesaurus

The measured conclusion of `../BENCHMARK.md` is that trait and ecological-relationship search
over BHL is best served by **Elasticsearch passages ranked with BM25 plus a curated multilingual
synonym thesaurus** — not by a cross-encoder reranker (which made results *worse* on BHL's OCR)
and not by a corpus-wide dense index (which does add recall, but costs 107 GB+ resident).

That makes this thesaurus the highest-return component in the design, which is why it gets its
own directory.

## Authorship, and what was actually measured

**I (Claude) wrote `trait-synonyms.txt` on 2026-09-28 as a test fixture.** It is not Rod's, not
derived from any published vocabulary, and not authoritative. Thirteen rules invented in a few
minutes to see whether the *mechanism* worked. They did:

| system | P@10 | nDCG@10 |
|---|---|---|
| BM25, `folding` analyser only | 0.650 | 0.757 |
| **+ those 13 rules** | **0.762** | **0.868** |
| + cross-encoder rerank (`bge-reranker-v2-m3`) | 0.700 | 0.811 |

Measured over 242,815 passages from 300 BHL-Light items, 8 trait queries, 189 pooled passages
graded blind. On the German query *"Nahrung der Raupe"* the rules took nDCG@10 from **0.270 to
0.588** — the single largest effect measured anywhere in this study.

The point is not that these 13 rules are good. It is that **thirteen rules written in minutes beat
a 568M-parameter neural reranker**, and a properly curated thesaurus should do considerably better.
`trait-synonyms.txt` SECTION 2 holds ~27 further rules that are plausible but **untested**.

## Wiring it up

`trait-index-settings.json` is a working index definition. Two rules that are not optional:

**1. Synonyms go in the SEARCH analyser, never the index analyser.** The index analyser is plain
`folding`; the search analyser is `folding_syn`. This is what lets the thesaurus be edited and
reloaded without reindexing 63.9M documents. Putting synonyms at index time bakes them into the
postings and every edit becomes a full reindex.

**2. `synonym_graph` must come AFTER `asciifolding`.** Not a style preference — verified:

| analyser chain | query `Bestäubung` | query `bestaubung` |
|---|---|---|
| `lowercase, asciifolding, synonym_graph` | → `bestaubung pollination` | → `bestaubung pollination` |
| `lowercase, synonym_graph, asciifolding` | → `bestaubung pollination` | → **`bestaubung` only** |

With the wrong order, the *unaccented* form silently stops expanding — and unaccented is exactly
what BHL's OCR produces, because it drops diacritics constantly. The rule would appear to work when
tested with correctly-spelled German and fail on the real corpus.

Also verified: **non-ASCII characters in the rules are safe.** Elasticsearch analyses the synonym
rules through the preceding filters, so `bestäubung` in the file becomes `bestaubung` internally and
matches both forms. There is no need to pre-fold the file, and doing so loses readability for no gain.

Use `synonym_graph`, not `synonym` — multi-word entries such as `stomach contents` and
`larval food plant` require the graph variant. `lenient: true` skips malformed rules instead of
failing the whole index.

## Growing it without breaking it

Each rule buys recall and can cost precision. The clearest example is already in SECTION 1:

```
host plant, hostplant, food plant, foodplant, larval host, ...
host, hosts, wirt, hote, hospedero
```

These are deliberately **separate**. Merging them would conflate the botanical sense (a plant an
insect larva eats) with the parasitological sense (an animal a worm lives in) — and BHL is full of
both. A query about butterfly host plants would start matching host–parasite checklists for
seabirds. Keep sets narrow and single-sense.

So: add a few rules, then measure.

```bash
# baseline, synonyms disabled
python3 eval/evaluate.py --index psg --no-synonyms
# current configuration
python3 eval/evaluate.py --index psg
```

`eval/qrels.json` holds **240 graded passages** across the 8 queries (0 = irrelevant, 1 = on topic
but states no trait, 2 = states the trait), including 51 from the dense-retrieval round. That is
reusable ground truth: it cost the most human-equivalent effort of anything here and should not be
thrown away.

**Its limits, stated plainly.** Eight queries is small. Judgments came from a single assessor
(Claude), blind to which system produced each passage but not to the task. Pooling means an unseen
passage scores 0, so the numbers are comparative, not absolute. And 68% of the pool graded 2 —
this corpus is saturated with natural-history content, leaving little headroom above 0.868. Use the
harness to detect regressions and relative gains, not to claim an absolute quality level.

## Where the remaining recall is

The thesaurus does not close the gap entirely. Dense retrieval found **3.8 relevant passages per
query that lexical search never surfaced at all** (`../BENCHMARK.md`, *Recall measured*) — real
content, including a moth host-plant table and wombat parasite-site records. Capturing that
corpus-wide needs 107 GB resident and does not fit the planned machine; a **trait-flagged subset of
~10% of pages costs ~14 GB** and does.

The order of work that follows from the measurements: build this lexical system first because it
wins on ranking and costs nothing extra, then add the targeted dense index for recall.

One caution for whoever curates this: dense retrieval's *worst* query was the cross-language one,
where it matched language and topic but not the trait. Multilingual coverage in the thesaurus is
doing work that embeddings did not replicate.
