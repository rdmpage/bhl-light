<?php

error_reporting(E_ALL);

require_once (dirname(dirname(__FILE__)) . '/config.inc.php');
require_once (dirname(dirname(__FILE__)) . '/couchsimple.php');
require_once (dirname(dirname(__FILE__)) . '/core.php');

//----------------------------------------------------------------------------------------
function get($url)
{
	global $config;
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	
	curl_setopt($ch, CURLOPT_HTTPHEADER, 
		array(
			"X-Api-Key: " . $config['datalab_api_key']
			)
		);
	
	$response = curl_exec($ch);
	if($response == FALSE) 
	{
		$errorText = curl_error($ch);
		curl_close($ch);
		die($errorText);
	}
	
	$info = curl_getinfo($ch);
	$http_code = $info['http_code'];
		
	curl_close($ch);
	
	return $response;
}

//----------------------------------------------------------------------------------------
function post($url, $data)
{
	global $config;
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  
	
	curl_setopt($ch, CURLOPT_HTTPHEADER, 
		array(
			"Content-type: multipart/form-data",
			"X-Api-Key: " . $config['datalab_api_key']
			)
		);
	
	$response = curl_exec($ch);
	if($response == FALSE) 
	{
		$errorText = curl_error($ch);
		curl_close($ch);
		die($errorText);
	}
	
	$info = curl_getinfo($ch);
	$http_code = $info['http_code'];
		
	curl_close($ch);
	
	return $response;
}

//----------------------------------------------------------------------------------------
// Convert WEBP to PDF using img2pdf
function webp2pdf($basedir, $num_pages)
{
	$files = scandir($basedir);
	
	$basename = basename($basedir);
	
	// print_r($files);
	
	$file_list = array();
	
	foreach ($files as $image_filename)
	{
		if (preg_match('/^[^\.].*\.webp/', $image_filename))
		{
			$source_filename = $basedir . '/' . $image_filename;
			$file_list[] = $source_filename;

		}
	}
	
	$num_images = count($file_list);
	
	$delta = $num_images - $num_pages;
	if ($delta > 0) // FFS IA and BHL, get your shit together
	{
		echo "*** Warning ***\n";
		echo "Number of pages [$num_pages differs from number of images [$num_images]\n";
		if ($delta == 2)
		{
			// assume first and last should be dropped
			array_shift($file_list);
			array_pop($file_list);
			
			echo "Trimmed first and last image from PDF\n";
		}
		if ($delta == 1)
		{
			// ?
		}
		
	}
	
	$file_list_filename =  $basename  . '.txt';
	
	file_put_contents($file_list_filename, join("\0", $file_list));
	
	$pdf_filename = $basename  . '.pdf';
	
	$command = 'img2pdf --from-file ' . $file_list_filename . ' -o ' . $pdf_filename;
	system($command);
	
	return $pdf_filename;
}

//----------------------------------------------------------------------------------------
// Convert WEBP to PDF using img2pdf, which requires that we make a list of images
// and concatenate those into a file.
// Return the filename of the PDF we create
function page_list_to_pdf($basedir, $pages, $suffix = '')
{
	global $config;
	
	$basename = basename($basedir);
	$pdf_filename = $config['datalab_tmp_dir'] . '/' . $basename  . $suffix . '.pdf';
	
	if (!file_exists($pdf_filename))
	{		
		$file_list = array();
		
		foreach ($pages as $image_filename)
		{
			$file_list[] = $basedir . '/' . $image_filename;
		}
		
		$file_list_filename =  $basename  . '.txt';
		
		file_put_contents($file_list_filename, join("\0", $file_list));
			
		$command = 'img2pdf --from-file ' . $file_list_filename . ' -o ' . $pdf_filename;
		system($command);
	}
	
	return $pdf_filename;
}

//----------------------------------------------------------------------------------------
// Send PDF to DataLab
function pdftolayout($upload_filename, $output_filename)
{
	$url = 'https://www.datalab.to/api/v1/layout';
	
	// upload and get layout
	$data = array(
		"file" => new CurlFile($upload_filename, mime_content_type($upload_filename), $upload_filename)
	);
	
	$result = null;
	
	$response = post($url, $data);
	
	$response_obj = json_decode($response);
	
	print_r($response_obj);
	
	if ($response_obj->success)
	{
		$max_polls = 300;
		for ($i = 0; $i < $max_polls; $i++)
		{
			// echo "polling [$i]\n";
			$json = get($response_obj->request_check_url);
			
			$result = json_decode($json);
			
			if ($result->status == 'complete')
			{
				// re fetch
				$json = get($response_obj->request_check_url);
				$result = json_decode($json);
				break;
			}
			
			usleep(1000000);
	
		}
	}
	
	$json = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	
	file_put_contents($output_filename, $json);
}

//----------------------------------------------------------------------------------------
// Given Internet Archive identifier create PDF from extracted images and get layout
function get_ai_layout($ia, $num_pages)
{
	global $config;
	
	echo "Processing $ia\n";
	echo "Create PDF from images\n";

	// Create PDF from webp images
	$dir = $config['s3'] . '/' . $ia . '_jp2';

	$upload_filename = webp2pdf($dir, $num_pages);
	
	echo "Get layout from DataLabl\n";
	
	// Get layout for PDF
	$output_filename = preg_replace('/\.pdf/', '.json', $upload_filename);
	pdftolayout($upload_filename, $output_filename);
	
	return $output_filename;
}

//----------------------------------------------------------------------------------------


$identifiers = array(
	//'europeanjournal236muse',
	//'australianentom44entoa',
	//'insectsofsamoaot01othe',
	//'insectsofsamoaot02natu',
	//'austrobaileya3queea',
	//'journalofbombay751978bomb', // failed, maybe too big
	//'bulletindumuseu42musea', // failed, maybe too big
	//'asiaticherpetolo05asia',
	
	//'europeanjournal128muse',
	//'austrobaileya6quee',
	
	//'europeanjournal123muse',
	//'australianentom44entoa',
	//'iberusrevistad3222014barc',
	//'metamorphosisauseptbuttf',
	//'insectsofsamoaot05othe',
	//'asiaticherpetolo05asia',
	
	//'europeanjournal27muse',
	//'notalepidopter3822015soci',
	//'bulletindumuseu41musea',
	
	//'austrobaileya12quee',
	
	//'biodiversitybio4teln',
	
	//'panpacificentom78vand',
	
	//'forktail2920unse',
	
	//'gardensquotbull00botae',
	
	'gardensquotbull00botad',
);

$chunk_size = 100;

foreach ($identifiers as $ia)
{
	// get text layout from CouchDB so we know how many pages to include in the PDF
	$layout = get_layout('layout/' . $ia);
	
	$pages = array();
	
	foreach ($layout->pages as $page)
	{
		$pages[] = preg_replace('/\.(djvu|jp2)/', '.webp', $page->internetarchive);
	}
	
	print_r($pages);
	
	// we may have to chunk the work if it is large
	$page_layout = array();
	
	$page_chunks = array_chunk($pages, $chunk_size);
	
	$num_chunks = count($page_chunks);
	
	for ($i = 0; $i < $num_chunks; $i++)
	{		
		// OK make PDF
		
		// Folder with images (locally mounted S3 drive)
		$dir = $config['s3'] . '/' . $ia . '_jp2';
		
		$upload_filename = page_list_to_pdf($dir, $page_chunks[$i], "-$i");
				
		echo "Get layout from DataLabl\n";
		
		// Get layout for PDF
		$output_filename = preg_replace('/\.pdf/', '.json', $upload_filename);
		pdftolayout($upload_filename, $output_filename);
				
		$json = file_get_contents($output_filename);
		
		$chunk_layout = json_decode($json);
		
		$page_layout[] = $chunk_layout;
		
	}
	
	// to do: merge layouts
	$num_layouts = count($page_layout);
	
	$doc = $page_layout[0];
	
	if ($num_layouts > 1)
	{
		for ($i = 1; $i < $num_layouts; $i++)
		{
			if ($page_layout[$i]->success)
			{
				$doc->pages = array_merge($doc->pages, $page_layout[$i]->pages);			
				$doc->page_count += count($page_layout[$i]->pages);
			}
		}
	}
	else
	{
		
	}

	if ($doc && $doc->success)
	{
		// add page name so we can get the images
		$n = count($layout->pages);
		for ($i = 0; $i < $n; $i++)
		{
			$doc->pages[$i]->internetarchive = $layout->pages[$i]->internetarchive;
		}	
	
		$force_upload = true;
		//$force_upload = false;
	
		$doc->_id = 'blocks/' . $ia;
		$doc->internetarchive = $ia;
		
		unset($doc->error); // IMPORTANT as otherwise when retrieving from CouchDB we always think we have an error!
		
		$exists = $couch->exists($doc->_id);
		
		if ($exists && !$force_upload)
		{
			echo "Have " . $doc->_id . " already!\n";
		}
		else
		{
			if ($exists && $force_upload)
			{
				$couch->add_update_or_delete_document(null, $doc->_id, 'delete');
			}
	
			$resp = $couch->send("PUT", "/" . $config['couchdb_options']['database'] . "/" . urlencode($doc->_id), json_encode($doc));
			var_dump($resp);	
		}
	}	
	
	
	
	
}




?>