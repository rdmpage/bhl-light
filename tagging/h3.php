<?php

require_once(dirname(__FILE__) . '/h3.php');

$uber_pdo = new PDO('sqlite:uber_h3.db');

//----------------------------------------------------------------------------------------
// retrieve data from database
function uber_db_get($sql)
{
	global $uber_pdo;
	
	$stmt = $uber_pdo->query($sql);

	$data = array();

	while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

		$item = new stdclass;
		
		$keys = array_keys($row);
	
		foreach ($keys as $k)
		{
			if ($row[$k] != '')
			{
				$item->{$k} = $row[$k];
			}
		}
	
		$data[] = $item;
	}	
	return $data;	
}

//----------------------------------------------------------------------------------------
function uber_db_put($sql)
{
	global $uber_pdo;
	
	$stmt = $uber_pdo->prepare($sql);
	
	if (!$stmt)
	{
		echo "\nPDO::errorInfo():\n";
		print_r($uber_pdo->errorInfo());
	}	
	
	$stmt->execute();
	
	if (!$stmt)
	{
		echo "\nPDO::errorInfo():\n";
		print_r($uber_pdo->errorInfo());
	}	
	
}


//----------------------------------------------------------------------------------------
// test whether source URL is already in database
function get_h3_from_cache($coords)
{
	$cells = array();
	
	$lonlat = join(",", $coords);

	$sql = 'SELECT * FROM uber_h3 WHERE lonlat="' . $lonlat . '" LIMIT 1';
	
	$data = uber_db_get($sql);
	
	foreach ($data as $row)
	{
		foreach ($row as $k => $v)
		{
			if (preg_match('/h3_(\d+)/', $k, $m))
			{
				$cells[$m[1]] = $v;
			}
		}
	}
		
	return $cells;
}

//----------------------------------------------------------------------------------------
function store_h3_in_cache($coords, $cells)
{
	$lonlat = join(",", $coords);

	$keys = array();
	$values = array();
	
	$keys[] = '"lonlat"';
	$values[] = "'" . $lonlat . "'";			
	
	foreach ($cells as $index => $name)
	{
		$keys[] =  '"h3_' . $index . '"';
		$values[] = "'" . $name . "'";		
	}
	
	$sql = 'REPLACE INTO uber_h3 (' . join(",", $keys) . ') VALUES (' . join(",", $values) . ');';

	uber_db_put($sql);
}

//----------------------------------------------------------------------------------------
function latlon2h3($coords)
{
	// use SQLite cache
	
	$cells = get_h3_from_cache($coords);
	
	if (count($cells) == 0)
	{
		//echo "Cache miss\n";

		$cells = array();
	
		$min_resolution = 1;
		$max_resolution = 9;
	
		for ($resolution = $min_resolution; $resolution <= $max_resolution; $resolution++)
		{
			$command = 'latLngToCell --resolution ' . $resolution . ' --latitude ' . $coords[1] . ' --longitude ' . $coords[0];
			
			$output = array();
			exec($command, $output);
			
			$cells[$resolution] = $output[0];
		}
		
		store_h3_in_cache($coords, $cells);
	}
	else
	{
		// echo "Cached hit\n";
	}

	return $cells;
}

if (0)
{
	$cells = get_h3_from_cache(array(100,30));
	print_r($cells);

}

?>
