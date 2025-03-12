<?php

//----------------------------------------------------------------------------------------
function scale_bbox(&$bbox, $scale)
{
	$bbox[0] *= $scale;
	$bbox[1] *= $scale;
	$bbox[2] *= $scale;
	$bbox[3] *= $scale;
}

?>
