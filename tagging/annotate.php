<?php

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/h3.php');

//----------------------------------------------------------------------------------------
// text is being annotated, highlight is bit being tagged, last_pos is last position 
// we tagged in this text, offset is offset with respect to larger document
function annotation_selector($text, $highlight, &$last_pos, $offset = 0)
{
	$flanking_length = 32;
	
	$selectors = array();
	
	// position
	$start = mb_strpos($text, $highlight, $last_pos, mb_detect_encoding($text));
	$length = mb_strlen($highlight, mb_detect_encoding($highlight));
	$end = $start + $length - 1;
	
	$selector = new stdclass;
	$selector->type = 'TextPositionSelector';
	$selector->start = (Integer)$start;
	$selector->end = (Integer)$end;
	
	$selectors[] = $selector; 
	
	// text loc
	$selector = new stdclass;
	$selector->type = 'TextQuoteSelector';
	$selector->exact = $highlight;
	
	$pre_length = min($start, $flanking_length);
	$pre_start = $start - $pre_length;	
	$selector->prefix = mb_substr($text, $pre_start, $pre_length, mb_detect_encoding($text)); 
	
	$post_length = 	min(mb_strlen($text, mb_detect_encoding($text)) - $end, $flanking_length);					
	$selector->suffix = mb_substr($text, $end + 1, $post_length, mb_detect_encoding($text));
	
	$selectors[] = $selector; 
			
	$last_pos = $end;
	
	return $selectors;
}

//----------------------------------------------------------------------------------------
/**
 * @brief Convert degrees, minutes, seconds to a decimal value
 *
 * @param degrees Degrees
 * @param minutes Minutes
 * @param seconds Seconds
 * @param hemisphere Hemisphere (optional)
 *
 * @result Decimal coordinates
 */
function degrees2decimal($degrees, $minutes=0, $seconds=0, $hemisphere='N')
{
	// ensure decimal point (if any) is a point, not a comma
	$degrees = str_replace(',', '.', $degrees);
	$minutes = str_replace(',', '.', $minutes);
	$seconds = str_replace(',', '.', $seconds);

	$result = $degrees;
	$result += $minutes/60.0;
	$result += $seconds/3600.0;
	
	//echo "seconds=$seconds|<br/>";
	
	if ($hemisphere == 'S')
	{
		$result *= -1.0;
	}
	if ($hemisphere == 'W')
	{
		$result *= -1.0;
	}
	// Spanish
	if ($hemisphere == 'O')
	{
		$result *= -1.0;
	}
	// Spainish OCR error
	if ($hemisphere == '0')
	{
		$result *= -1.0;
	}
	
	return $result;
}

//----------------------------------------------------------------------------------------
function toPoint($matches)
{
	$feature = new stdclass;
	$feature->type = "Feature";
	$feature->geometry = new stdclass;
	$feature->geometry->type = "Point";
	$feature->geometry->coordinates = array();
			
	$degrees = $minutes = $seconds = 0;		
		
	if (isset($matches['latitude_seconds']))
	{
		$seconds = $matches['latitude_seconds'];
		
		if ($seconds == '')
		{
			$seconds = 0;
		}
		
	}
	
	if (isset($matches['latitude_minutes']))
	{
		$minutes = $matches['latitude_minutes'];
	}
	$degrees = $matches['latitude_degrees'];
		
	$feature->geometry->coordinates[1] = degrees2decimal($degrees, $minutes, $seconds, $matches['latitude_hemisphere']);

	$degrees = $minutes = $seconds = 0;	
	
	if (isset($matches['longitude_seconds']))
	{
		$seconds = $matches['longitude_seconds'];
		
		if ($seconds == '')
		{
			$seconds = 0;
		}
	}
	if (isset($matches['longitude_minutes']))
	{
		$minutes = $matches['longitude_minutes'];
	}
	$degrees = $matches['longitude_degrees'];
	
	$feature->geometry->coordinates[0] = degrees2decimal($degrees, $minutes, $seconds, $matches['longitude_hemisphere']);
	
	// ensures that JSON export treats coordinates as an array
	ksort($feature->geometry->coordinates);
	
	return $feature;
}

//----------------------------------------------------------------------------------------

function add_geo_match_to_annotation($matches, $text, &$annotations)
{
	$last_pos = 0;
	
	foreach ($matches as $match)
	{
		$annotation = new stdclass;			
		$annotation->text = $match[0];			
		$annotation->target = new stdclass;
		$annotation->target->selector = annotation_selector($text, $match[0], $last_pos);
		$annotation->geojson = toPoint($match);	
		
		$annotation->uber_h3 = latlon2h3($annotation->geojson->geometry->coordinates);	
		
		$annotations[] = $annotation;
	}
}

//----------------------------------------------------------------------------------------
// tag coordinates in text
function tag_geo($text)
{
	$annotations = array();

	$DEGREES_SYMBOL 		=  '[˚|°|º]';
	$MINUTES_SYMBOL			= '(\'|’|\′|\´)';
	$SECONDS_SYMBOL			= '("|\'\'|’’|”|\′\′|\´\´|″|\′\′)';
	
	$INTEGER				= '\d+';
	$FLOAT					= '\d+([\.|,]\d+)?';
	
	$LATITUDE_DEGREES 		= '[0-9]{1,2}';
	$LONGITUDE_DEGREES 		= '[0-9]{1,3}';
	
	$LATITUDE_HEMISPHERE 	= '[N|S]';
	$LONGITUDE_HEMISPHERE 	= '[W|E]';
	
	$ES_LATITUDE_HEMISPHERE 	= '[N|S]';
	$ES_LONGITUDE_HEMISPHERE 	= '[O|E]';
		
	$flanking_length = 50;
	
	$results = array();
		
	if (preg_match_all("/
		(?<latitude_degrees>$LATITUDE_DEGREES)
		$DEGREES_SYMBOL
		\s*
		(?<latitude_minutes>$FLOAT)
		\s*
		$MINUTES_SYMBOL?
		\s*
		(
		(?<latitude_seconds>$FLOAT)
		$SECONDS_SYMBOL
		)?
		\s*
		(?<latitude_hemisphere>$LATITUDE_HEMISPHERE)
		,?
		(\s+-)?
		;?
		\s*
		(?<longitude_degrees>$LONGITUDE_DEGREES)
		$DEGREES_SYMBOL
		\s*
		(?<longitude_minutes>$FLOAT)
		\s*
		$MINUTES_SYMBOL?
		\s*
		(
		(?<longitude_seconds>$FLOAT)
		$SECONDS_SYMBOL
		)?
		\s*
		(?<longitude_hemisphere>$LONGITUDE_HEMISPHERE)
		
	/xu",  $text, $matches, PREG_SET_ORDER))
	{
		$last_pos = 0;
	
		foreach ($matches as $match)
		{
			$annotation = new stdclass;			
			$annotation->text = $match[0];
			$annotation->target = new stdclass;
			$annotation->target->selector = annotation_selector($text, $match[0], $last_pos);
			$annotation->geojson = toPoint($match);				
			$annotations[] = $annotation;
		}	
	}
	
	// 29.6° N, 101.8° E
	if (preg_match_all("/
		(?<latitude_degrees>$FLOAT)
		$DEGREES_SYMBOL
		\s*
		(?<latitude_hemisphere>$LATITUDE_HEMISPHERE)
		,
		\s+
		(?<longitude_degrees>$FLOAT)
		$DEGREES_SYMBOL
		\s*
		(?<longitude_hemisphere>$LONGITUDE_HEMISPHERE)		
	/xu",  $text, $matches, PREG_SET_ORDER))
	{
		add_geo_match_to_annotation($matches, $text, $annotations);	
	}
	
	
	// N27.21234º, E098.69601º
	if (preg_match_all("/
		(?<latitude_hemisphere>$LATITUDE_HEMISPHERE)
		(?<latitude_degrees>$FLOAT)
		$DEGREES_SYMBOL
		,
		\s+
		(?<longitude_hemisphere>$LONGITUDE_HEMISPHERE)
		(?<longitude_degrees>$FLOAT)
		$DEGREES_SYMBOL		
	/xu",  $text, $matches, PREG_SET_ORDER))
	{
		add_geo_match_to_annotation($matches, $text, $annotations);	
	}
	
	// N25°59', E98°40'
	if (preg_match_all("/
		(?<latitude_hemisphere>$LATITUDE_HEMISPHERE)
		\s*
		(?<latitude_degrees>$LATITUDE_DEGREES)
		$DEGREES_SYMBOL
		(?<latitude_minutes>$INTEGER)
		$MINUTES_SYMBOL
		(
		(?<latitude_seconds>$FLOAT)
		$SECONDS_SYMBOL
		)?		
		,
		\s+
		(?<longitude_hemisphere>$LONGITUDE_HEMISPHERE)
		\s*
		(?<longitude_degrees>$LONGITUDE_DEGREES)
		$DEGREES_SYMBOL		
		(?<longitude_minutes>$INTEGER)
		$MINUTES_SYMBOL
		(
		(?<longitude_seconds>$FLOAT)
		$SECONDS_SYMBOL
		)?		
	/xu",  $text, $matches, PREG_SET_ORDER))
	{
		//print_r($matches);
		add_geo_match_to_annotation($matches, $text, $annotations);
	}
	
	// Spanish https://doi.org/10.21068/c2018.v19s1a11
	// 4°19´44”N y 71°43´54.1”O
	if (preg_match_all("/
		(?<latitude_degrees>$LATITUDE_DEGREES)
		$DEGREES_SYMBOL
		(?<latitude_minutes>$INTEGER)
		$MINUTES_SYMBOL
		\s*
		(
		(?<latitude_seconds>$FLOAT)
		$SECONDS_SYMBOL
		)?
		\s*
		(?<latitude_hemisphere>$ES_LATITUDE_HEMISPHERE)		
		\s*y\s*
		(?<longitude_degrees>$LONGITUDE_DEGREES)
		$DEGREES_SYMBOL		
		(?<longitude_minutes>$INTEGER)
		$MINUTES_SYMBOL
		\s*
		(
		(?<longitude_seconds>$FLOAT)
		$SECONDS_SYMBOL
		)?		
		\s*
		(?<longitude_hemisphere>$ES_LONGITUDE_HEMISPHERE)
	/xu",  $text, $matches, PREG_SET_ORDER))
	{
		add_geo_match_to_annotation($matches, $text, $annotations);
	}	
	

	return $annotations;
}

//----------------------------------------------------------------------------------------

if (0)
{
	// I think these are supposed to be degrees and decimal minutes (comma) delimited,
	// but they have been converted into minutes and seconds(?)	
	// page/62177680
	// 21°04’421”°N/105°21°865”’E

	$text = '21°04’421”°N/105°21°865”’E,';
	
	$annotations = tag_geo($text);
	
	print_r($annotations);



}


?>
