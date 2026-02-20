<?php

require_once (dirname(__FILE__) . '/sqltojson.php');



//----------------------------------------------------------------------------------------
// http://stackoverflow.com/a/5996888/9684
function translate_quoted($string) {
  $search  = array("\\t", "\\n", "\\r");
  $replace = array( "\t",  "\n",  "\r");
  return str_replace($search, $replace, $string);
}

//----------------------------------------------------------------------------------------

$annotationPage = new stdclass;
$annotationPage->{'@type'} = ['AnnotationPage'];
$annotationPage->items = array(); 

// model on hypothes.is page note which is a page-level annotation

$titles = array();


$filename = 'colDP/nameusage.csv';

$headings = array();

$row_count = 0;

$file = @fopen($filename, "r") or die("couldn't open $filename");
		
$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$row = fgetcsv(
		$file_handle, 
		0, 
		translate_quoted(','),
		translate_quoted('"') 
		);
		
	$go = is_array($row);
	
	if ($go)
	{
		if ($row_count == 0)
		{
			$headings = $row;		
		}
		else
		{
			$obj = new stdclass;
		
			foreach ($row as $k => $v)
			{
				if ($v != '')
				{
					$obj->{$headings[$k]} = $v;
				}
			}
		
			//print_r($obj);	
			
			if (isset($obj->namePublishedInPageLink))
			{
				if (preg_match('/https?:\/\/www.biodiversitylibrary.org\/page\/(\d+)/', $obj->namePublishedInPageLink, $m))
				{				
					$PageID =$m[1];
					$pair = get_item_order_for_page($PageID);
					
					if (isset($pair[0]))
					{
						
						// curious about what tiles we need
						$TitleID = get_title_for_item($pair[0]);
						if (!isset($titles[$TitleID]))
						{
							$t = get_title($TitleID);
							$titles[$TitleID] = $t->name;
						}
					
						// 296840
						// 148261
						if (in_array($pair[0], array(296840)))
						{						
							$annotation  = new stdclass;
							$annotation->text = $obj->scientificName;
							
							$annotation->body = new stdclass;
							$annotation->body->id = $obj->link;
							
							$annotation->target = new stdclass;
							$annotation->target->source = "page/" . $pair[0];
							
							if (!isset($annotationPage->items[$pair[1]]))
							{
								$annotationPage->items[$pair[1]] =  array();
							}
							$annotationPage->items[$pair[1]][] = $annotation;
						}
					}
						
					
				}
				
			}

		}
	}	
	$row_count++;
}

//echo json_encode($annotationPage);

print_r($titles);


?>

