#!/usr/bin/env python3
"""Score a trait-search configuration against the judged query set.

A synonym thesaurus can only be grown safely if each addition is measured: a rule that
buys recall on one query can cost precision on another. This re-runs the 8 judged
queries and reports P@10 / nDCG@10 so a change can be compared against the baseline.

    python3 evaluate.py --index psg --es http://localhost:9200
    python3 evaluate.py --index psg --no-synonyms      # baseline, synonyms disabled

Baseline measured 2026-09-28 over 242,815 BHL passages with the 13 SECTION 1 rules:

    BM25, folding only .................. P@10 0.650   nDCG@10 0.757
    + trait synonyms .................... P@10 0.762   nDCG@10 0.868   <- target
    + cross-encoder rerank (v2-m3) ...... P@10 0.700   nDCG@10 0.811

Judgments are 0 = irrelevant, 1 = on topic but states no trait, 2 = states the trait.
They cover only passages some system has already surfaced, so an unseen passage counts
as 0 -- the usual pooling bias. Treat these as comparative, not absolute, numbers.
"""
import argparse, json, math, os, urllib.request

HERE = os.path.dirname(os.path.abspath(__file__))


def search(es, index, query, analyzer=None, size=10):
    def clause(kind, extra):
        body = {"query": query, **extra}
        if analyzer:
            body["analyzer"] = analyzer
        return {kind: {"text": body}}

    payload = {
        "size": size,
        "_source": ["pid"],
        "query": {"bool": {"should": [
            clause("match_phrase", {"slop": 3, "boost": 3}),
            clause("match", {"minimum_should_match": "60%"}),
        ], "minimum_should_match": 1}},
    }
    req = urllib.request.Request(f"{es}/{index}/_search",
                                 data=json.dumps(payload).encode(),
                                 headers={"Content-Type": "application/json"})
    hits = json.load(urllib.request.urlopen(req))["hits"]["hits"]
    return [h["_source"]["pid"] for h in hits]


def dcg(grades):
    return sum((2 ** g - 1) / math.log2(i + 2) for i, g in enumerate(grades))


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--es", default="http://localhost:9200")
    ap.add_argument("--index", default="psg")
    ap.add_argument("--no-synonyms", action="store_true",
                    help="override the search analyser with plain 'folding' (baseline)")
    args = ap.parse_args()

    queries = json.load(open(os.path.join(HERE, "queries.json")))
    qrels = json.load(open(os.path.join(HERE, "qrels.json")))
    analyzer = "folding" if args.no_synonyms else None

    p10, n10 = [], []
    print(f"{'query':36s} {'P@10':>6s} {'nDCG@10':>8s}")
    for qid, q in queries.items():
        run = search(args.es, args.index, q, analyzer)
        rel = qrels.get(qid, {})
        grades = [rel.get(pid, 0) for pid in run]
        ideal = sorted(rel.values(), reverse=True)[:10]
        p = sum(1 for g in grades if g == 2) / 10
        n = dcg(grades) / dcg(ideal) if dcg(ideal) > 0 else 0.0
        p10.append(p); n10.append(n)
        print(f"{qid + ' ' + q[:32]:36s} {p:6.3f} {n:8.3f}")

    label = "BM25 folding only" if args.no_synonyms else "with trait synonyms"
    print(f"\n{label}: P@10 {sum(p10)/len(p10):.3f}   nDCG@10 {sum(n10)/len(n10):.3f}")
    if not args.no_synonyms:
        print("baseline to beat (13 measured rules): P@10 0.762   nDCG@10 0.868")


if __name__ == "__main__":
    main()
