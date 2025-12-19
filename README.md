# BHL Light

Lightweight interface to the Biodiversity Heritage Library (BHL)

## Problems

- journalofbombay721975bomb text and images out of alignment

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

[Nouveau](https://neighbourhood.ie/blog/2024/10/24/first-steps-with-nouveau) used to provide full-text search. 

#### MacOS

Go to Applications folder, find `Apache CouchDB`, Ctrl-click to get context menu, select **Show Package Contents**, go to Contents/Resources/couchdbx-core and start the server:

```
java -jar nouveau/lib/nouveau-1.0-SNAPSHOT.jar server etc/nouveau.yaml
```

#### Linux (cloud)

This requires Java >= 11

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

#### Add a search index to CouchDB

The first time this view is called it can take a while as the search index in Nouveau has to be generated.

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

## Experiments

### Document structure

The folder `datalab` has code to run BHL content on Datalab and retrieve layout structure (e.g., headers, captions, lists, figures), which are uploaded to CouchDB as `blocks-`. This enables us to display figures in BHL-Light.

Big challenge is reconciling OCR layout from IA with page layout from Datalab. if we do that, then we can list text blocks and their locations, and build a model of the semantics of an article. In other words, for a given block of text we will have the text and also a layout classification. If we combine that with the actual text we should be able to classify that block (e.g., “abstract”, “reference”, etc.). We also want to be able to associate figures with their captions so we can have a text-based image search.

### Links

Idea is that links from external sources (e.g., Wikipedia, Wikipsecies, taxonomic databases such as Afromoths and Catalogue of Life) tells us something about those pages: they are important (and hence maybe should rank higher in search results) and that these pages likely describe new species, name changes, etc.

`import/afro.php’ generates some JSON we can use, see `$display_google_sidebar` flag in `index.php` for code that experiments with this display. Note that having ticks on the sidebar is hard to see (we can’t distinguish between individual ticks).

See https://doriantaylor.com/document-stats for another idea, which is a scrolling list of document titles with curved lines linking to the document date.

## Comparisons with Plazi

It is useful to compare BHL and Plazi for the same content, e.g.  item/253713 and https://ejt.biodiversity.hasdai.org/records/3ketn-e3390 and http://treatment.plazi.org/id/C93D87F0FFFCFF9F8E3BFB7DFA88FA6E 

## Zookeys and JATS XML layout

Would be interesting to overlay JATS XML onto page images, e.g. for Zookeys content. In particular we could annotate references with links to BHL content. Would need to use string matching to match blocks from XML with blocks from layout.

Nice example https://www.biodiversitylibrary.org/page/64400329#page/1/mode/1up

## Taxonomic names

Taxonomic names are added by getting names for each page from BHL data dump. We then attempt to locate those names in the OCR text (layout/) so that we can highlight them. These locations are stored as annotations.

Initial inspection suggests many names are not taxonomic, e.g. Vailima is a place in Samoa and also fish genus. It appears as a locality in the Insects of Somoa series. Might we be able to use the context around the word (i.e., the prefix and suffix of the annotation) to train a model to distinguish between taxonomic and other uses of a word?

For example, in Item 137726
- Island, **Vailima**, 12.x11.1925, Buxton and Hopkin
- ens: one male (holotype) Upolu, **Vailima**, 1.1925, 1,200 ft. ;
- female (paratype) **Vailima**, Upolu; two females, one male (

Another example, `Camorta` is an island, and most results seem to be geographic, e.g.:

- ted the central islands of Camorta, Nancowry,
- Camorta, kept up their curious pid
- serted settlement at Camorta in the Nicobars, whe
- Camorta and Car Nicobar, where it
- essa, Bompoka, Tillangchong, Camorta, Nancowry, Trinkut
- was obtained by Davison at Camorta in the
- found it not uncommon at Camorta ; from his account it app
- enty or eighty together at Camorta. I did not come across it
- the abandoned settlement at Camorta in

 




