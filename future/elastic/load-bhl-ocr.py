#!/usr/bin/env python3
"""Stream the BHL OCR full-text export into Elasticsearch, one document per page.

Reads bhl-ocr-YYYYMMDD.tar.bz2 as described in "OCR Text Export Data Dictionary.md".
Nothing is extracted to disk: the archive is decompressed and consumed sequentially,
so ~40 GB compressed never becomes ~134 GB on disk.

    # fastest: parallel bzip2 decompression, 16 cores
    lbzip2 -dc bhl-ocr-20261115.tar.bz2 | ./load-bhl-ocr.py --stdin --es http://localhost:9200

    # or let the script pick a decompressor
    ./load-bhl-ocr.py --archive bhl-ocr-20261115.tar.bz2

Two things about the archive shape drive the design:

1. PAGES APPEAR TWICE. A part (article) is a span of pages inside an item, so the
   same PageID shows up under item-NN/ and again under part-NN/. Using the PageID
   as the Elasticsearch _id makes the load idempotent, and because tar order puts
   "item-" before "part-" alphabetically, item entries create the document and part
   entries then add partid via a partial update. Verify the assumption for your
   archive with --report-only before a full run.

2. THE TEXT CARRIES TRANSCRIPTION MARK-UP. [[strike]] wraps text the transcriber
   deleted and must not be indexed; [[illustration]] wraps figure alt text, which is
   worth its own field; the remaining [[tag]] markers would otherwise make "footnote",
   "unclear" and friends searchable terms on every marked-up page. [text addition]
   brackets often hold the currently accepted species name, so the brackets go and
   the text stays.
"""
import argparse, io, json, os, re, shutil, subprocess, sys, tarfile, time, urllib.request

DIR_RE  = re.compile(r'/(item|part)-(\d{6,})/[^/]+$')
TAIL_RE = re.compile(r'(\d+)-(\d+)\.txt$')

STRIKE  = re.compile(r'\[\[strike\]\].*?\[\[/?strike\]\]', re.I | re.S)
ILLUS   = re.compile(r'\[\[illustration\]\](.*?)\[\[?/illustration\]\]', re.I | re.S)
TAG     = re.compile(r'\[\[/?[a-z]+\]\]', re.I)
TBLSEP  = re.compile(r'^\s*\|[\s\-|:]+\|\s*$', re.M)
BRACKET = re.compile(r'\[([^\[\]]{1,80})\]')
WS      = re.compile(r'[ \t]+')


def normalise(text):
    """Strip transcription mark-up; return (clean_text, illustration_alt_texts)."""
    text = STRIKE.sub(' ', text)
    illus = [m.strip() for m in ILLUS.findall(text) if m.strip()]
    text = ILLUS.sub(lambda m: ' ' + m.group(1) + ' ', text)
    text = TAG.sub(' ', text)
    text = TBLSEP.sub(' ', text)
    text = BRACKET.sub(r'\1', text)
    return WS.sub(' ', text).strip(), illus


def parse(path):
    """-> (kind, id, page_id, seq) or None. Type and id come from the directory
    because the dictionary's own examples show filenames both with and without the
    'item-' prefix."""
    d, t = DIR_RE.search(path), TAIL_RE.search(path)
    if not d or not t:
        return None
    return d.group(1), int(d.group(2)), int(t.group(1)), int(t.group(2))


def opener(args):
    if args.stdin:
        return tarfile.open(fileobj=sys.stdin.buffer, mode='r|*')
    for tool in ('lbzip2', 'pbzip2', 'bzip2'):
        if shutil.which(tool):
            if tool != 'bzip2':
                print(f"# decompressing with {tool}", file=sys.stderr)
            p = subprocess.Popen([tool, '-dc', args.archive], stdout=subprocess.PIPE)
            return tarfile.open(fileobj=p.stdout, mode='r|*')
    return tarfile.open(args.archive, mode='r|bz2')


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--archive'); ap.add_argument('--stdin', action='store_true')
    ap.add_argument('--es', default='http://localhost:9200')
    ap.add_argument('--index', default='bhl-pages')
    ap.add_argument('--batch', type=int, default=2000)
    ap.add_argument('--checkpoint', default='load-bhl-ocr.checkpoint')
    ap.add_argument('--limit', type=int, default=0, help='stop after N entries (smoke test)')
    ap.add_argument('--only', choices=('item', 'part', 'both'), default='both',
                    help="restrict to item or part entries. NOTE part-* sorts after item-* in "
                         "the archive, so a --limit run never reaches parts; use --only part "
                         "to exercise that path (still costs a full decompression pass).")
    ap.add_argument('--item-meta',
                    help='JSON map {"itemid": {"year": 1897, "titleid": "10088", '
                         '"volume": "40", "ia": "..."}} joined onto each page. The OCR archive '
                         'supplies only page_id/itemid/partid/seq/text, so year -- which by_year '
                         'and year_range both need -- must come from the BHL TSV export.')
    ap.add_argument('--report-only', action='store_true',
                    help='parse and count without indexing; use this first')
    args = ap.parse_args()
    if not args.stdin and not args.archive:
        ap.error('give --archive or --stdin')

    meta = {}
    if args.item_meta:
        meta = json.load(open(args.item_meta))
        print(f"# joined metadata for {len(meta):,} items", file=sys.stderr)

    meta = {}
    if args.item_meta:
        meta = json.load(open(args.item_meta))
        print(f"# joined metadata for {len(meta):,} items", file=sys.stderr)

    resume = None
    if os.path.exists(args.checkpoint):
        resume = open(args.checkpoint).read().strip()
        print(f"# resuming after {resume}", file=sys.stderr)

    url = f"{args.es}/{args.index}/_bulk"

    def flush(lines):
        if args.report_only or not lines:
            return
        req = urllib.request.Request(url, data=("\n".join(lines) + "\n").encode(),
                                     headers={"Content-Type": "application/x-ndjson"})
        resp = json.load(urllib.request.urlopen(req))
        if resp.get("errors"):
            for it in resp["items"]:
                err = (it.get("index") or it.get("update") or {}).get("error")
                if err:
                    print("ERROR:", json.dumps(err)[:400], file=sys.stderr)
                    raise SystemExit(1)

    buf, last = [], None
    n = {"item": 0, "part": 0, "skipped": 0, "unparsed": 0, "empty": 0}
    t0 = time.time()
    tf = opener(args)
    for member in tf:
        if not member.isfile() or not member.name.endswith('.txt'):
            continue
        if resume:                                   # tar is sequential; skip until past it
            if member.name == resume:
                resume = None
            n["skipped"] += 1
            continue
        parsed = parse(member.name)
        if not parsed:
            n["unparsed"] += 1
            continue
        kind, ident, page_id, seq = parsed
        if args.only != 'both' and kind != args.only:
            n["skipped"] += 1
            continue
        raw = tf.extractfile(member).read().decode('utf-8', 'replace')
        text, illus = normalise(raw)
        if not text:
            n["empty"] += 1
            continue
        n[kind] += 1

        if kind == 'item':
            doc = {"page_id": str(page_id), "itemid": str(ident), "seq": seq, "text": text}
            if illus:
                doc["illustration"] = illus
            extra = meta.get(str(ident))
            if extra:
                doc.update({k: v for k, v in extra.items() if v not in (None, '')})
            buf.append(json.dumps({"index": {"_id": str(page_id)}}))
            buf.append(json.dumps(doc, ensure_ascii=False))
        else:
            # the page almost certainly already exists from its item; add partid only.
            # doc_as_upsert covers the case where it does not.
            buf.append(json.dumps({"update": {"_id": str(page_id)}}))
            buf.append(json.dumps({"doc": {"partid": str(ident), "page_id": str(page_id),
                                           "seq": seq, "text": text},
                                   "doc_as_upsert": True}, ensure_ascii=False))
        last = member.name

        if len(buf) >= args.batch * 2:
            flush(buf); buf = []
            with open(args.checkpoint, 'w') as fh:
                fh.write(last)
            done = n["item"] + n["part"]
            if done % 200_000 < args.batch:
                el = time.time() - t0
                print(f"  {done:,} pages  {el/60:5.1f} min  ({done/el:,.0f}/s)  "
                      f"items {n['item']:,} parts {n['part']:,}", flush=True, file=sys.stderr)
        if args.limit and (n["item"] + n["part"]) >= args.limit:
            break
    flush(buf)
    if last:
        with open(args.checkpoint, 'w') as fh:
            fh.write(last)
    el = time.time() - t0
    print(f"\ndone in {el/60:.1f} min: {n['item']:,} item pages, {n['part']:,} part pages, "
          f"{n['empty']:,} empty, {n['unparsed']:,} unparsed, {n['skipped']:,} skipped on resume")
    print(f"distinct pages indexed ~= item pages ({n['item']:,}); part entries updated existing docs")


if __name__ == '__main__':
    main()
