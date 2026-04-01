<?php

error_reporting(E_ALL);
require_once (dirname(__FILE__) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/api_utils.php');

/*
//--------------------------------------------------------------------------------------------------
function get_x($id)
{
	global $config;
	global $couch;
	
	$obj = null;	
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . urlencode($id));
	
	$obj = json_decode($resp);
	
	return $obj;
}
*/


//--------------------------------------------------------------------------------------------------
function display_item($id, $callback = '')
{
	global $config;
	global $couch;
	
	$status = 404;	
	$result = null;
	
	$id = 'item/' . $id;
	
	$parameters = array(
		'key' 			=> '"' . $id . '"',
	);

	$url = '_design/item/_view/about?' . http_build_query($parameters);
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);
	
	$response_obj = json_decode($resp);
	
	if ($response_obj)
	{
		foreach ($response_obj->rows as $row)
		{
			$result = $row->value;
		}
	}
	
	if ($result)
	{
		$status = 200;
	}
	
	api_output($result, $callback, $status);
}


//--------------------------------------------------------------------------------------------------
function display_item_parts($id, $callback = '')
{
	global $config;
	global $couch;
	
	$status = 404;	
	$result = [];
	
	$id = 'item/' . $id;
	
	$parameters = array(
		'key' 			=> '"' . $id . '"',
	);

	$url = '_design/item/_view/parts?' . http_build_query($parameters);
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);
	
	$response_obj = json_decode($resp);
	
	if ($response_obj)
	{
		foreach ($response_obj->rows as $row)
		{
			$result[] = $row->value;
		}
	}
	
	if (count($result) > 0)
	{
		$status = 200;
	}
	
	api_output($result, $callback, $status);
}

//--------------------------------------------------------------------------------------------------
function display_page_names($id, $callback = '')
{
	global $config;
	global $couch;
	
	$status = 404;	
	$result = [];
	
	$parameters = array(
		'key' => $id // key is an integer FFS!
	);

	$url = '_design/name/_view/pageid-names?' . http_build_query($parameters);
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);
	
	$response_obj = json_decode($resp);
	
	if ($response_obj)
	{
		foreach ($response_obj->rows as $row)
		{
			$result = $row->value;
		}
	}
	
	if ($result)
	{
		$status = 200;
	}
	
	api_output($result, $callback, $status);
}

//--------------------------------------------------------------------------------------------------
function display_page_text($id, $callback = '')
{
	global $config;
	global $couch;
	
	$status = 404;	
	$result = [];
	
	$parameters = array(
		'key' => $id // key is an integer FFS!
	);

	$url = '_design/text/_view/pageid?' . http_build_query($parameters);
	
	$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);
	
	$response_obj = json_decode($resp);
	
	if ($response_obj)
	{
		foreach ($response_obj->rows as $row)
		{
			$result = $row->value;
			$result = preg_split('/\R/u', $result);
		}
	}
	
	if ($result)
	{
		$status = 200;
	}
	
	api_output($result, $callback, $status);
}


//--------------------------------------------------------------------------------------------------
function main()
{
	global $config;

	$callback = '';
	$handled = false;
	
	$post_content = file_get_contents('php://input');
	
	// If no query parameters 
	if (count($_GET) == 0 && $post_content == '')
	{
		default_display();
		exit(0);
	}
	
	if (isset($_GET['callback']))
	{	
		$callback = $_GET['callback'];
	}
	
	$debug = false;			
	if (isset($_GET['debug']))
	{
		$debug = true;
	}	
	
	// Submit job
	
	// get item
	if (!$handled)
	{
		if (isset($_GET['item']))
		{	
			$id = $_GET['item'];
			
			if (isset($_GET['parts']))
			{			
				display_item_parts($id, $callback);
				$handled = true;
			}		
			
			if (!$handled)
			{			
				display_item($id, $callback);
				$handled = true;
			}
		}
	}
	
	// get page
	if (!$handled)
	{
		if (isset($_GET['page']))
		{	
			$id = $_GET['page'];

			if (isset($_GET['names']))
			{			
				display_page_names($id, $callback);
				$handled = true;
			}	
			
			if (isset($_GET['text']))
			{			
				display_page_text($id, $callback);
				$handled = true;
			}		
				
		}
	}
	
	
	if (!$handled)
	{
		default_display();
	}

}

main();

?>
