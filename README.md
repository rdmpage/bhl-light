# bhl-light
Lightweight interface to the Biodiversity Heritage Library (BHL)


## Basic idea

Get basic BHL metadata from [BHL Data](https://www.biodiversitylibrary.org/data/TSV). Import that into a SQLite database.

Extract records for titles, items, etc. from SQLite, format as simple objects and store as JSON documents in CouchDB.

Create views for these documents.

Have a set of functions that retrieve data/views from CouchDB and convert to JSON-LD “like” documents (`core.php`) that use (almost exclusively) terms from https://schema.org. Hence when creating web pages we should only need to think in terms of JSON-LD, not the CouchDB documents, nor the original BHL database structure. Note that typically the CouchDB documents are JSON-LD. Exceptions are things that are easy to express in plain JSON, such as document layout.

The JSON-LD documents are then used to create web pages to view the data. Emphasis is on simplicity: more CSS, less Javascript.

Avoid deeply nested JSON-LD documents (i.e., “framed”), instead create arrays of relevant objects for easy access. For example, an array may contain a representation of the object, and also a “DataFeed” list of, say, items.

## BHL and Internet Archive

BHL is really two things, a database with BHL identifiers (e.g., TitleID, ItemID, PageID, and the scanned images and OCR text in Internet Archive (IA). Linking the two is sometimes problematic because the pages in an IA archive need not all be in the corresponding BHL item, IA doesn’t always know the BHL PageIDs of the pages in its scans.

To get around this the basic page identifier is its order 0, …, n - 1 in BHL and whichever IA file we use for OCR text (e.g., hOCR or DjVu). BHL page order (`SequenceOrder`) starts at 1, so we need to be careful to not get zero-based and one-based counting mixed up.

This also means that in some case we use IA identifiers (which BHL refers to as `BarCode`) for items, especially for anything that involves images or text.

## Layout

IA provides OCR text in at least three formats, ABBYY, DjVu, and hOCR. Here I use hOCR (HTML) and DjVu (XML) (the later only if hOCR is missing).

The OCR files are converted to a JSON format based on that returned by the [Datalab](https://www.datalab.to) tools as we will be exploring using them to add strcuture to the BHL pages.


## Image server

Setup an instance of imgproxy running on Heroku, with Cloudflare sitting in front of it. URLs are signed.

