<?php
//--------------------------------------------------------------------------------------
// Extract one JSON document per BHL page from the CouchDB "layout" and "nametagged"
// documents, as newline-delimited JSON. This is the common input for every benchmark
// in future/BENCHMARK.md: Elasticsearch indexing, text embedding, and image embedding
// all read the same JSONL so the corpus can never drift between experiments.
//
//   php -d memory_limit=2G future/extract-pages.php OUT.jsonl [MAX_ITEMS]
//
// Resumable: if OUT.jsonl exists, items already written are skipped.
//--------------------------------------------------------------------------------------

require_once(dirname(__FILE__) . '/../env.php');

$out_path = isset($argv[1]) ? $argv[1] : 'pages.jsonl';
$max_items = isset($argv[2]) ? (int)$argv[2] : 0;

$base = getenv('COUCHDB_PROTOCOL') . getenv('COUCHDB_USERNAME') . ':' . getenv('COUCHDB_PASSWORD')
	. '@' . getenv('COUCHDB_HOST') . ':' . getenv('COUCHDB_PORT') . '/bhl-lite';

function couch_get($url)
{
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 600);
	$r = curl_exec($ch);
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	return $code == 200 ? $r : null;
}

// Which items have we already done? Lets a run be interrupted and picked up again.
$done = array();
if (file_exists($out_path))
{
	$fh = fopen($out_path, 'r');
	while (($line = fgets($fh)) !== false)
	{
		$d = json_decode($line, true);
		if ($d && isset($d['ia'])) $done[$d['ia']] = 1;
	}
	fclose($fh);
	echo "resuming: " . count($done) . " items already extracted\n";
}

$all = json_decode(couch_get($base . '/_all_docs?limit=200000'), true);
$layouts = array();
foreach ($all['rows'] as $row)
{
	if (strpos($row['id'], 'layout/') === 0) $layouts[] = $row['id'];
}
unset($all);
sort($layouts);

$out = fopen($out_path, 'a');
$t0 = microtime(true);
$n_items = 0;
$n_pages = 0;

foreach ($layouts as $layout_id)
{
	$ia = substr($layout_id, strlen('layout/'));
	if (isset($done[$ia])) continue;
	if ($max_items && $n_items >= $max_items) break;

	$layout = json_decode(couch_get($base . '/' . rawurlencode($layout_id)), true);
	if (!$layout || !isset($layout['pages'])) continue;

	// Names are in a parallel document keyed by the same page index. Absent for some items.
	$names_doc = json_decode((string)couch_get($base . '/' . rawurlencode('nametagged/' . $ia)), true);
	$annotations = ($names_doc && isset($names_doc['annotations'])) ? $names_doc['annotations'] : array();

	foreach ($layout['pages'] as $i => $page)
	{
		$lines = array();
		if (isset($page['text_lines']))
		{
			foreach ($page['text_lines'] as $tl) $lines[] = $tl['text'];
		}
		$text = implode("\n", $lines);

		$names = array();
		if (isset($annotations[$i]['names'])) $names = array_values(array_unique($annotations[$i]['names']));

		// image_bbox is [x, y, width, height] in source-image pixels
		$w = isset($page['image_bbox'][2]) ? (int)$page['image_bbox'][2] : 0;
		$h = isset($page['image_bbox'][3]) ? (int)$page['image_bbox'][3] : 0;

		$doc = array(
			'page_id'  => isset($page['bhl_pageid']) ? (string)$page['bhl_pageid'] : null,
			'ia'       => $ia,
			'seq'      => (int)$i,
			'item'     => isset($layout['bhl_item']) ? (string)$layout['bhl_item'] : null,
			'year'     => isset($names_doc['datePublished']) ? (string)$names_doc['datePublished'] : null,
			'image'    => $ia . '_jp2/' . preg_replace('/\.(djvu|jp2)$/', '.webp', (string)$page['internetarchive']),
			'width'    => $w,
			'height'   => $h,
			'n_lines'  => count($lines),
			'n_words'  => $text === '' ? 0 : count(preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY)),
			'names'    => $names,
			'text'     => $text,
		);

		fwrite($out, json_encode($doc, JSON_UNESCAPED_UNICODE) . "\n");
		$n_pages++;
	}

	unset($layout, $names_doc, $annotations);
	$n_items++;

	if ($n_items % 25 == 0)
	{
		$dt = microtime(true) - $t0;
		fprintf(STDERR, "  %d items  %d pages  %.1fs  (%.1f items/s, %.0f pages/s)\n",
			$n_items, $n_pages, $dt, $n_items / $dt, $n_pages / $dt);
	}
}

fclose($out);
$dt = microtime(true) - $t0;
printf("done: %d items, %d pages in %.1fs (%.1f items/s, %.0f pages/s)\n",
	$n_items, $n_pages, $dt, $dt ? $n_items / $dt : 0, $dt ? $n_pages / $dt : 0);
