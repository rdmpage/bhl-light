# Indexing BHL on the Ubuntu server

Concrete steps for a test run and then a full load of the 64M-page OCR export. Sizing and
rationale are in `../BENCHMARK.md`; the traps are in `../INDEXING-NOTES.md`.

Target: 128 GB RAM, 4 TB SSD. Expect **~180 GB** for the index, ~12% of the disk.

## 0. Before anything: the metadata gap

The OCR archive supplies only **6 of the 16** fields `page-index.json` declares:

| from the archive | needs another source |
|---|---|
| `page_id`, `itemid`, `partid`, `seq`, `text`, `illustration` | `year`, `titleid`, `volume`, `ia`, `names`, `entities`, `tags`, `locations`, `geo_annotations`, `thumbnail` |

**`year` matters most** — both the `by_year` histogram and the `year_range` aggregation in
`bhl-elastic-test/search.php` depend on it, and neither works without it. It comes from BHL's
TSV data export (<https://www.biodiversitylibrary.org/data/TSV>), not the OCR archive.

Build an itemid → metadata map once and let the loader join it in a single pass:

```json
{ "10285":  {"year": 1897, "titleid": "10088", "volume": "40", "ia": "tijdschriftvoore40nede"},
  "124544": {"year": 1923, "titleid": "6928",  "volume": "12", "ia": "annalsmagazineof12lond"} }
```

At ~330k items that file is maybe 40 MB and loads into memory fine. Without it the text index
still works; you just lose the date facet.

## 1. Elasticsearch

```bash
sudo apt install elasticsearch lbzip2        # lbzip2 matters: see step 3
```

In `/etc/elasticsearch/jvm.options.d/heap.options`:

```
-Xms31g
-Xmx31g
```

**31 GB, not 32.** Past ~32 GB the JVM drops compressed ordinary object pointers and you get
*less* usable heap. The remaining ~95 GB stays with the OS as page cache for a 180 GB index —
about half resident, which is a well-provisioned ratio for an inverted index.

Put the thesaurus where the index expects it:

```bash
sudo mkdir -p /etc/elasticsearch/analysis
sudo cp ../trait-search/trait-synonyms.txt /etc/elasticsearch/analysis/
```

## 2. Create the index

`page-index.json` ships with `number_of_shards: 24`. Match shard count roughly to core count,
keeping each shard in the 10–50 GB range — at 180 GB, 12–24 shards all work. Strip the
`_comment` keys before POSTing.

```bash
python3 - <<'EOF'
import json, urllib.request
b = json.load(open('page-index.json'))
b['settings']['number_of_shards'] = 16          # ~11 GB per shard
b['settings']['refresh_interval'] = '-1'        # off during bulk load
def strip(o):
    if isinstance(o, dict):  return {k: strip(v) for k, v in o.items() if k != '_comment'}
    if isinstance(o, list):  return [strip(v) for v in o]
    return o
urllib.request.urlopen(urllib.request.Request(
    'http://localhost:9200/bhl-pages', data=json.dumps(strip(b)).encode(),
    headers={'Content-Type': 'application/json'}, method='PUT'))
print('created')
EOF
```

## 3. Smoke test, then subset, then full

**Always `--report-only` first.** It parses and counts without indexing, and confirms the one
assumption the whole design rests on — that part pages duplicate item pages (see
`../INDEXING-NOTES.md`, and the loader's own docstring).

```bash
# a few thousand entries, no indexing: are the item/part counts sane?
lbzip2 -dc bhl-ocr-20261115.tar.bz2 | ./load-bhl-ocr.py --stdin --report-only --limit 20000

# subset: first ~200k pages, actually indexed
rm -f load-bhl-ocr.checkpoint
lbzip2 -dc bhl-ocr-20261115.tar.bz2 \
  | ./load-bhl-ocr.py --stdin --limit 200000 --item-meta item-meta.json

# full run
rm -f load-bhl-ocr.checkpoint
lbzip2 -dc bhl-ocr-20261115.tar.bz2 \
  | ./load-bhl-ocr.py --stdin --item-meta item-meta.json
```

**Use `lbzip2`, not `bzip2`.** bzip2 is single-threaded and 40 GB of it will dominate the
runtime; parallel decompression across 16 cores turns decompression from the bottleneck into a
non-issue.

**A `--limit` run never reaches the part pages.** `part-*` sorts after `item-*` in the archive,
so a subset run only exercises the item path. `--only part` covers the other one, but tar is
sequential so it still costs a full decompression pass — which is why a full run is usually the
better second step.

The loader checkpoints after every batch; delete `load-bhl-ocr.checkpoint` to start over, leave
it to resume.

## 4. After loading

```bash
curl -X PUT 'localhost:9200/bhl-pages/_settings' -H 'Content-Type: application/json' \
  -d '{"refresh_interval":"30s"}'
curl -X POST 'localhost:9200/bhl-pages/_forcemerge?max_num_segments=1'   # hours; do it once
curl 'localhost:9200/_cat/indices/bhl-pages?v&h=docsCount,segmentsCount,storeSize'
```

Expect roughly **3,150 bytes per page** — that figure came within 4% on a 1.94M-page BHL-Light
build, so a materially different number means something is wrong.

## 5. Checks worth running

```bash
# the ligature case: all four must return the same count
for t in 'Muscidæ' 'Muscidae' 'muscidae' 'MUSCIDÆ'; do
  curl -s localhost:9200/bhl-pages/_count -H 'Content-Type: application/json' \
    -d "{\"query\":{\"match\":{\"text\":\"$t\"}}}"; echo " <- $t"
done

# did part pages merge rather than duplicate? partid present AND itemid present
curl -s 'localhost:9200/bhl-pages/_search?size=2' -H 'Content-Type: application/json' \
  -d '{"query":{"exists":{"field":"partid"}},"_source":["page_id","itemid","partid"]}'

# struck-through text must NOT be searchable; tag names must not be terms
curl -s localhost:9200/bhl-pages/_count -H 'Content-Type: application/json' \
  -d '{"query":{"match_phrase":{"text":"[[strike]]"}}}'
```

## 6. Two things to fix in `search.php` before serving this

1. **Cache the `year_range` aggregation.** It is `global`, so it ignores the query and recomputes
   the same two numbers on every search. Measured at ~19 ms on 2M documents and scaling at about
   n^0.77, that is ~270 ms per search at 64M — roughly half the total query cost, for a constant.
   Compute it once after a load.
2. **Expect ~1 s on broad queries** with the full four-aggregation set at 64M pages, dropping to
   ~0.5 s once the above is cached. Narrow queries stay well under 100 ms.
