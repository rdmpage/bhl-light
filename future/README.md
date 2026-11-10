# Indexing BHL: measurements, configs and runbooks

Work towards a page-level index of all of BHL — Elasticsearch full text, image vectors, text
embeddings and RDF — sized against a real machine rather than guessed at.

Nearly everything here is **measured**. Where a figure is an estimate or an extrapolation it says
so, and the two biggest extrapolations have since been checked against real builds (page-count and
index-size, both within 4–10%).

## If you are about to index on the server, start here

1. **[`elastic/RUNBOOK.md`](elastic/RUNBOOK.md)** — the actual sequence: heap, index creation,
   smoke test, subset run, full run, force-merge, verification queries.
2. **[`INDEXING-NOTES.md`](INDEXING-NOTES.md)** — 28 operational findings, each marked
   **[M]** measured here · **[C]** carried from `bhl-all-the-images` · **[D]** derived. Read items
   22a, 27 and 28 before the first load; they cover things that fail silently.
3. **[`elastic/load-bhl-ocr.py`](elastic/load-bhl-ocr.py)** — streams `bhl-ocr-*.tar.bz2` straight
   into Elasticsearch. Resumable. Run `--report-only` first.

## Headline conclusions

| question | answer |
|---|---|
| Can one box host all of BHL? | Yes. ~180 GB index, 12% of 4 TB; 128 GB RAM gives ~50% resident |
| Page count | **63,862,938** (exact, from QLever) |
| Index size | ~3,150 B/page — validated to 96% against a 1.94M-page build |
| Trait/ecology search | BM25 + a multilingual synonym thesaurus. A cross-encoder reranker measured **worse** |
| Dense text embeddings | Add real recall (3.8 relevant passages/query) but need a *targeted* subset, not a corpus-wide index |
| Image search | CLIP ViT-B/32, 512-d `halfvec`, HNSW ~78 GB. Binary quantisation **rejected** on measured recall |
| HNSW build at 60M | ~2–4 h *if* the graph is resident; ~13–20 h if not (see 22a) |
| GPU | Not needed. Elasticsearch cannot use one; bulk embedding is a one-off rental |

## Files

| file | what it is |
|---|---|
| `SIZING-STUDY.md` | the original brief: what to find out |
| `BENCHMARK.md` | the answer, with every measurement and its method |
| `INDEXING-NOTES.md` | the traps, and the configs that avoid them |
| `OCR Text Export Data Dictionary.md` | BHL's description of the OCR archive |
| `elastic/page-index.json` | page-level mapping, smoke-tested |
| `elastic/load-bhl-ocr.py` | archive → Elasticsearch loader |
| `elastic/RUNBOOK.md` | server sequence |
| `trait-search/` | synonym thesaurus, index settings, 240 graded passages, eval harness |
| `extract-pages.php` | CouchDB → page JSONL, for BHL-Light rather than the full archive |
| `bhl-elastic-test-*` | mapping, query and notes carried from that repo |
| `couchdb-items-titles.json` | BHL-Light titles/items view |

## Carrying this to a new repo

The portable parts are **`elastic/`**, **`trait-search/`** and **`INDEXING-NOTES.md`** — none of
them depend on BHL-Light or CouchDB. `extract-pages.php` and `couchdb-items-titles.json` are
BHL-Light-specific and can stay behind. `BENCHMARK.md` is the evidence base: worth copying so the
numbers keep their provenance.

## Still open

- **Document structure.** Indexing raw OCR means running heads and back-of-book indexes rank
  highest — a `Muscidae` search returns index pages above content. This is also the best
  explanation for the reranker result. Block labels (Mistral/Datalab) would fix it; only 22 of
  6,507 BHL-Light items currently have them.
- **The metadata join.** The OCR archive supplies 6 of 16 mapped fields. `year` is not among them
  and `by_year` needs it; it comes from BHL's TSV export.
- **Reranker relevance on clean text.** The negative result was measured on noisy IA OCR. If
  Mistral-quality text lands, re-run `trait-search/eval/` before writing reranking off.
- **pgvector HNSW build at 60M** is extrapolated from 2M, not measured at scale.
