# BHL Light

Lightweight interface to the Biodiversity Heritage Library (BHL)


## Basic idea

Get basic BHL metadata from [BHL Data](https://www.biodiversitylibrary.org/data/TSV). Import that into a SQLite database. Extract records for titles, items, etc. from SQLite, reformat as simple objects and store as JSON documents in CouchDB.

### CouchDB views for these documents.

Have a set of functions that retrieve data/views from CouchDB and convert to JSON-LD “like” documents (`core.php`) that use (almost exclusively) terms from https://schema.org. Hence when creating web pages we should only need to think in terms of JSON-LD, not the CouchDB documents, nor the original BHL database structure. Note that typically the CouchDB documents are JSON-LD. Exceptions are things that are easier to express in plain JSON, such as document layout.

The JSON-LD documents are then used to create web pages to view the data. Emphasis is on simplicity: more CSS, less Javascript.

Avoid deeply nested JSON-LD documents (i.e., “framed” documents). Instead create arrays of relevant objects for easy access. For example, this array may contain a representation of the object (e.g., a title), and also a “DataFeed” list of, say, items.

## BHL and Internet Archive

BHL is really two things, a database with BHL identifiers (e.g., TitleID, ItemID, PageID), and the scanned images and OCR text in Internet Archive (IA). Linking the two is sometimes problematic because the pages in an IA archive need not all be in the corresponding BHL item, and IA doesn’t always know the BHL PageIDs of the pages in its scans.

To get around this the basic page identifier is its order 0, …, *n* - 1 in BHL and whichever IA file we use for OCR text (e.g., hOCR or DjVu). BHL page order (`SequenceOrder`) starts at 1, so we need to be careful to not get zero-based and one-based counting mixed up.

This also means that in some cases we use IA identifiers (which BHL refers to as `BarCode`) for items, especially for anything that involves images or text.

## Layout

IA provides OCR text in at least three formats, ABBYY, DjVu, and hOCR. Here I use hOCR (HTML) and DjVu (XML) (the later only if hOCR is missing).

The OCR files are converted to a JSON format based on that returned by the [Datalab](https://www.datalab.to) tools as we will be exploring using them to add structure to the BHL pages.

Note that this means that the smallest unit of OCR text is a line, rather than a word or a character. Hence the OCR is less granular than provided by hOCR or DjVu files. This makes the OCR files smaller, at the cost of not knowing exactly where each word boundary is.


## Image server

Setup an instance of [imgproxy](https://imgproxy.net) running on Heroku, with Cloudflare sitting in front of it. URLs are signed to prevent anyone else using this instance of imgproxy.

## CouchDB

Initially create database locally, but need to replicate it to a  server in the cloud. Experiments with Hetzner 8Gb servers crashed, so now using 16Gb server.

CouchDB on cloud server was installed from scratch using  [binary packages](https://docs.couchdb.org/en/stable/install/unix.html#installation-using-the-apache-couchdb-convenience-binary-packages).

### Nouveau search

[Nouveau](https://neighbourhood.ie/blog/2024/10/24/first-steps-with-nouveau) used to provide full-text search. This requires Java >= 11:

```
apt install openjdk-11-jre-headless
```

Add following setting to `/opt/couchdb/etc/local.ini`

```
[nouveau]
enable = true
```

```
cd /opt/couchdb
nohup java -jar nouveau/lib/nouveau-1.0-SNAPSHOT.jar server etc/nouveau.yaml
```

Add a search index to CouchDB. The first time this view is called it can take a while as the search index in Nouveau has to be generated.

```
{
  "_id": "_design/search",
  "nouveau": {
    "full-text": {
      "index": "function(doc){ if (doc.name) { index('text', 'default', doc.name) }}"
    }
  }
}
```



