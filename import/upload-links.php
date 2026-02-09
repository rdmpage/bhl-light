<?php

// Extract data from SQLite and import into CouchDB

require_once (dirname(dirname(__FILE__)) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/sqltojson.php');


$PageID = 43637832;

$ItemID = get_item_for_page($PageID);

echo $ItemID . "\n";

?>
