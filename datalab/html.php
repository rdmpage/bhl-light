<?php

function layout_to_html($layout, $images = array())
{
	$html = '';
	$html .= '<html>';
	$html .=  '<head>';
	$html .=  '<style>

			.page {
				background-color:white;
				position:relative;
				margin: 0 auto;
				/* border:1px solid black; */
				margin-bottom:1em;
				margin-top:1em;	
				box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
				
				
			}
		
	
.Text { background:green; opacity:0.2; } /* Paragraph */
.TextInlineMath { background:green; opacity:0.2; } /* Paragraph */

.Figure { background:red; opacity:0.2; }
.Picture { background:red; opacity:0.2; }


.Caption { background:yellow; opacity:0.2; }
.Table { background:blue; opacity:0.2; }
.PageHeader { background:orange; opacity:0.2; }
.PageFooter { background:orange; opacity:0.2; }
.SectionHeader { background:red; opacity:0.2; }
.Title { background:blue; opacity:0.2; }
.ListItem { background:blue; opacity:0.2; }
.Footnote { background:orange; opacity:0.2; }

	</style>';
	$html .=  '</head>';
	
	$html .=  '<body>';
	
	for ($i = 0; $i < $layout->page_count; $i++)
	{
		$scale = 1;
		if (count($images) > 0)
		{			
			$scale =  $images[$i]->width / ($layout->pages[$i]->image_bbox[2] - $layout->pages[$i]->image_bbox[0]);
			$html .= '<div class="page" style="width:' . $images[$i]->width . '">';
		
			$html .= '<img style="border:1px solid rgb(192,192,192);" src="' . $images[$i]->path . '">';
		}
		else
		{
			$html .= '<div class="page" style="width:' . ($layout->pages[$i]->image_bbox[2] - $layout->pages[$i]->image_bbox[0]) . ';height:' . ($layout->pages[$i]->image_bbox[3] - $layout->pages[$i]->image_bbox[1]) . '">';	
		}
				
		foreach ($layout->pages[$i]->bboxes as $block)
		{
			$html .= '<div class="' . $block->label . '" style="position:absolute;border:1px solid black;'
				. 'left:' . $block->bbox[0] * $scale . 'px;'
				. 'top:' .  $block->bbox[1] * $scale . 'px;'
				. 'width:' . ($block->bbox[2] - $block->bbox[0]) * $scale . 'px;'
				. 'height:' . ($block->bbox[3] - $block->bbox[1]) * $scale . 'px;'
				. '"></div>';
		
		}
		
		
		$html .= '</div>';
	}
	
	$html .= '</body>';
	$html .= '</html>';
	
	return $html;
}

$filename = 'europeanjournal236muse_jp2.json';
$filename = 'australianentom44entoa_jp2.json';

$json = file_get_contents($filename);

$layout = json_decode($json);

echo layout_to_html($layout);
?>
