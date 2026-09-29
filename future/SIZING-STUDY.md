# BHL server sizing study: brief for Claude Code

## Goal

Estimate the hardware (RAM, disk, CPU, and rough cost) needed for one server, or a small
number of servers, that hosts all of BHL with four services:

1. **Elasticsearch** full-text search, indexed at page level.
2. **Image search**: vector index of image embeddings at page level (or figure level).
3. **Text embeddings** for semantic search and RAG.
4. **RDF**: a triple store / SPARQL endpoint for BHL metadata and derived data.

BHL-Light is the reference implementation. Measure what BHL-Light actually stores for a
sample, then extrapolate to all of BHL. Numbers below come from earlier design
discussions. Most of them are back-of-envelope estimates, not measurements. Treat them as
hypotheses to check, and replace them with measured figures wherever possible.

## Background: BHL-Light

- BHL metadata, OCR text and layout (from Datalab) are stored as JSON documents in CouchDB.
- The web front end is a small set of PHP scripts.
- Layout extraction gives figures, captions, tables and references, not just pages.
  That matters for sizing, because figures and reference strings become indexable units
  in their own right.

## Corpus scale (to confirm)

- Pages: roughly 60–64M (62M was used in the IIIF/scandata work).
- Items: over 300,000 (one Internet Archive scandata file per item).
- Source data: the `bhl-open-data` bucket on AWS (us-east-2) holds page images
  (JPEG 2000), OCR and BHL's TSV exports. It does not mirror IA scandata files.
- Page images at ~1 MB each is ~64 TB. That is a transfer and processing cost, not
  something the server needs to hold.

**Task:** get current counts of titles, items, parts and pages from the BHL exports, and
record the date of the snapshot.

## 1. Elasticsearch (page-level full text)

Design decisions already made:

- One document per page, holding the OCR text plus identifiers (page, item, title) and
  any names found on the page.
- Analysis chain: `asciifolding` already maps æ→ae and œ→oe, so check with the
  `_analyze` API before adding a separate ligature `char_filter`.
- OCR errors: use query-time fuzziness (`fuzziness: AUTO`, `prefix_length: 4`). This
  costs nothing at index time.
- Optional n-gram subfield (`min_gram` 5–6, `max_gram` ~10) for broken tokens. This
  makes the index much bigger, so size it separately.
- Phonetic analysis only as a last resort.
- Analyzer changes need a full reindex. Use a new index plus an alias switch, and get
  the analyzers right before indexing all of BHL.

No size estimate has been made yet. **Task:**

- Index a representative sample (e.g. 100k–1M pages from BHL-Light, spread across eras
  and languages) with the planned mapping.
- Record index size per page with and without the n-gram subfield, and with and without
  `_source` storing the full text.
- Extrapolate to the full page count. Note JVM heap needs and the rule of thumb that the
  OS page cache should hold as much of the index as possible.

Note: Elasticsearch can also do dense-vector kNN. Record whether it could host the
vector indexes too, instead of Postgres, so the two options can be compared.

## 2. Image search (image embeddings)

Design decisions already made:

- Model: CLIP `clip-vit-base-patch32`, 512 dimensions. Also used for zero-shot tagging
  (map, chart, sonogram, photograph, drawing, etc.). `clip-vit-large-patch14` (768-d) is
  the upgrade path if quality is poor.
- Store: PostgreSQL with pgvector. Features include text-to-image search, upload an
  image as a query, and "similar images".
- Embedding generation is a batch Python job (MPS on a Mac, or a GPU on AWS). PHP handles
  queries and the UI.
- Open question: are the vectors per **page** or per **extracted figure** (Datalab
  figure regions)? Figure extraction could give more than 64M vectors (several figures
  on one page) or far fewer (most pages have no figures). This is the biggest unknown for
  sizing this component.

Estimates for 64M vectors at 512 dimensions:

| Representation | Per vector | Raw vectors | HNSW index (~2×) |
|---|---|---|---|
| `vector` (fp32) | 2 KB | 128 GB | ~256 GB |
| `halfvec` (fp16) | 1 KB | 64 GB | ~128 GB |
| binary / SBQ | 64 B | 4 GB | ~8 GB kept in RAM |

- A full-precision in-RAM HNSW index is not practical on one affordable machine.
- Recommended: binary quantisation, or pgvectorscale StreamingDiskANN with SBQ. The small
  compressed index stays in RAM, full vectors stay on NVMe for reranking.
- Including metadata (page ID, item, title, bounding box, caption, caption FTS index), the
  estimated total on disk is 400 GB–1 TB.
- Index build over 64M rows takes hours to days. Bulk load first, build the index
  afterwards, set `maintenance_work_mem` to 16–32 GB and raise
  `max_parallel_maintenance_workers`.
- Embedding pass: about 60 GPU-hours on one consumer GPU. Downloading and decoding the
  images takes longer than inference.

**Task:** from BHL-Light layout data, measure figures per page (mean and distribution)
on a sample, and use it to get the real vector count.

## 3. Text embeddings (semantic search / RAG)

Design decisions already made:

- Model: a multilingual embedder, BGE-M3 (1024-d) as first choice, because much of the
  relevant literature is in German, French and Latin. Don't reuse the CLIP text encoder;
  it's too weak for documents and has a short token limit.
- Unit of embedding: **page level** keeps the count near 60M. Paragraph-level chunking
  retrieves better, but gives hundreds of millions of vectors. One compromise: a cheap
  first pass that flags trait-bearing paragraphs (diet, hosts, habitat, behaviour) and
  embeds only those.
- Search is two-stage and hybrid. Lexical filtering (names index, full text) picks
  candidates, and vectors rank them. Lexical search handles taxon names, which
  embeddings are weak at. Embeddings handle differences in wording ("stomach contents",
  "feeds chiefly on", "Nahrung") and cross-language matches.
- Annotation is lazy. Annotate passages only when a query reaches them, and cache the
  results as W3C Web Annotations (target = page plus text span, with the annotator's
  name and version). Nothing is annotated in bulk up front.

Estimates at page level (60M × 1024-d):

- fp32: ~245 GB of raw vectors (plus index).
- int8: ~60 GB. Binary would be smaller still.
- pgvectorscale / DiskANN is the way to scale this within Postgres.

Before committing, check whether embeddings are worth it: on a few journals, compare
BM25, BM25 plus a hand-built trait synonym list, and hybrid search with embeddings, using
20–30 realistic queries and judging the top 10 results. If the synonym list gets close,
the embedding layer may not be needed.

**Task:** size both page-level and paragraph-level options. For paragraph level, measure
paragraphs per page from BHL-Light layout data.

## 4. RDF / triple store

Current state:

- A BHL RDF graph already exists, served over SPARQL with an MCP server. It covers titles,
  items, parts (articles), pages with sequence order and page numbers, creators, and
  page-level `dwc:scientificName` tags.
- Candidate stores: Oxigraph (lightweight; weak query planner; RocksDB takes roughly
  2–4× the N-Triples input size on disk), QLever (built for very large graphs; index
  builds are heavier and batch-style), Jena Fuseki (safe default).
- Treat the triple store as derived data. Source files on disk are the truth, and the
  store can be rebuilt from them.

Decision already taken:

- Page image geometry (width and height for IIIF manifests) is **not** stored as RDF.
  62M pages × 3–4 triples would be 200M+ triples and would dominate the store. It goes
  in a plain side table keyed on `(barcode, leaf)` instead.

Likely future growth: extracted references (BiRO/CiTO), annotations, typed external
links (protologue links from nomenclators, GBIF, Wikidata), geotags, and extracted
facts with provenance.

**Task:**

- Count current triples by predicate. Pages with names will probably dominate.
- Measure on-disk size for the chosen store(s) at the current triple count.
- Project growth: triples per page for names, references and annotations.

## Hosting: buy a machine vs. cloud

The plan is to **buy one machine** and run it at home. The study must answer two
questions: what spec that machine needs, and what the same workload would cost in the
cloud (for comparison, and as a fallback).

### Candidate machine

- Baseline: 128 GB RAM, 8 TB SSD (priced, affordable).
- Stretch: 256 GB RAM.
- Test the baseline first. Report whether 128 GB is enough, and what 256 GB would buy
  (lower query latency, running more index builds at once).

### Preliminary rollup (to be replaced by measurements)

| Component | Disk (rough) | RAM when serving (rough) |
|---|---|---|
| Elasticsearch, no n-gram field | 150–300 GB | 31 GB heap plus page cache |
| Elasticsearch, with n-gram field | 0.5–1 TB | as above, more page cache |
| Image vectors plus metadata (quantised) | 0.4–1 TB | ~10 GB hot index |
| Text vectors, page level (quantised, full vectors on disk) | 0.3–0.5 TB | ~10–20 GB hot index |
| RDF store | 0.1–0.4 TB (unknown) | depends on store |
| Postgres shared_buffers, OS | — | 16–32 GB |

Assumptions behind the Elasticsearch row: ~2–3 KB of OCR text per page on average, and
an index about 1–1.5× the raw text. Measure both.

Working conclusions to test:

- **Disk:** live data is probably 1.5–3 TB. 8 TB leaves room for rebuilding an index
  next to the live one (alias swap), raw vectors, source text and scratch space. Check
  that the SSD is NVMe. Two drives are better than one (e.g. Elasticsearch on one,
  Postgres on the other, or RAID 1).
- **RAM:** 128 GB should work **only if** vectors are quantised (DiskANN/SBQ or binary)
  and big index builds are run one at a time. The peak is during builds, not serving.
  256 GB mainly lets more of the Elasticsearch index stay in page cache, and allows
  builds while serving.
- **Upgrade path:** if buying 128 GB now, use DIMMs that leave free slots (e.g.
  2 × 64 GB, not 4 × 32 GB) and check the board's maximum. On consumer platforms, four
  DDR5 DIMMs often run at lower speeds. A workstation platform with ECC is the more
  robust choice for long-running builds.
- **GPU:** not needed on the home machine. Encoding a single query (CLIP or BGE-M3) on
  CPU is fast enough. **Bulk embedding runs on AWS**, next to the `bhl-open-data`
  bucket, as a one-off GPU spot job. Only the vectors (hundreds of GB) are downloaded.
  Downloading ~64 TB of page images to home isn't realistic.
- **Running costs at home:** power at ~150 W continuous is about 1,300 kWh/year
  (roughly £300–400/year at UK prices). Other factors: home upload bandwidth and uptime,
  if the service is public; noise; a separate backup drive. Indexes can be rebuilt, but
  a rebuild takes days, so back them up.

### Cloud comparison (prices to re-check at time of writing)

- **Rented dedicated server (Hetzner):** 128 GB AX-class boxes from ~€124/month. The
  AX162-R (48-core EPYC, 256 GB ECC, 2 × 1.92 TB NVMe) is listed by third-party price
  trackers at ~€229–244/month plus a setup fee of a few hundred euros. Disks would need
  upgrading to reach ~8 TB.
- **Hyperscaler (AWS):** 256 GB memory-optimised instances are roughly $1,300–1,600/month
  on demand, plus a few hundred dollars/month for ~4 TB of block storage. Managed RDS
  costs more.
- Report the break-even point: purchase price ÷ (Hetzner monthly − home running costs).

The open question remains: can **all four services share one box**, or should they be
split (e.g. Elasticsearch on one machine, Postgres vectors plus the triple store on
another)?

## Suggested method

1. Pick a stratified sample from BHL-Light (by era, language and title type) and write
   down how it was chosen.
2. For each service, load the sample and measure: disk size, RAM needed for acceptable
   query latency, index build time, and a few typical query times.
3. Extrapolate linearly to the full corpus. Flag anything that won't scale linearly
   (HNSW memory, n-gram fields).
4. Fill in the summary table below, giving low and high estimates.
5. Recommend a configuration (one server or several) with the reasoning.

| Component | Unit count | Disk (low–high) | RAM for good performance | Build time | Notes |
|---|---|---|---|---|---|
| Elasticsearch | | | | | |
| Image vectors | | | | | |
| Text vectors | | | | | |
| RDF store | | | | | |
| Side tables / metadata | | | | | |
| **Total** | | | | | |

## Conventions

PHP 7. Allman braces. Vanilla JavaScript and CSS, no frameworks. KISS. Python only for
the ML parts (embedding generation). Scripts should be resumable and idempotent, and
should log failures rather than stopping.
