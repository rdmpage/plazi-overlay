<?php

// Plazi XML

// Can we reverse engine the Plazi markup to map it back to the underlying document?

// Plazi doesn't record whole page size, but gives coordinates for some elements in the form
// box[minx, maxx, miny, maxy]
// These coords are w.r.t. to a single page, and the page number is an attribute of the element.
// Hence we can (sort of) reconstruct Plazi's understanidng of the structure of a document.
//


// Plazi use icepdf to handle PDFs, exmaples onlien for ice suggest that 200 dpi is the default reoslution.
// 190 dpi seems to closely match the actual boxes produced.

// need to udnerstand Plazi document structure, especially tables and figure captions...


//require_once (dirname(__FILE__) . '/spatial.php');


//----------------------------------------------------------------------------------------
function get_common_node_details($xml_node)
{
	$node = new stdclass;
	$node->type = $xml_node->nodeName;

	$attributes = array();
	$attrs = $xml_node->attributes; 

	foreach ($attrs as $i => $attr)
	{
		$attributes[$attr->name] = $attr->value; 
	}
	
	// by default use node id
	if (isset($attributes['id']))
	{
		$node->id = $attributes['id'];
	}
		
	$pts = array();
	
	if (count($pts) == 0)
	{					
		if (isset($attributes['targetBox']))
		{
			$box = $attributes['targetBox'];
			$box = str_replace('[', '', $box);
			$box = str_replace(']', '', $box);
			$pts = explode(",", $box);
		}					
	}

	if (count($pts) == 0)
	{					
		if (isset($attributes['captionTargetBox']))
		{
			$box = $attributes['captionTargetBox'];
			$box = str_replace('[', '', $box);
			$box = str_replace(']', '', $box);
			$pts = explode(",", $box);
			
			$node->id = $attributes['captionTargetId'];
		}					
	}
	
	if (count($pts) == 0)
	{					
		if (isset($attributes['blockId']))
		{
			$box = $attributes['blockId'];
			$box = preg_replace('/^\d+\./', '', $box);
			$box = str_replace('[', '', $box);
			$box = str_replace(']', '', $box);
			$pts = explode(",", $box);
			
			$node->id = $attributes['blockId'];
		}					
	}

	if (count($pts) == 0)
	{					
		if (isset($attributes['targetBox']))
		{
			$box = $attributes['targetBox'];
			$box = str_replace('[', '', $box);
			$box = str_replace(']', '', $box);
			$pts = explode(",", $box);
		}					
	}
	

		
	if (count($pts) > 0)
	{
		$x1 = $pts[0];
		$x2 = $pts[1];
		$y1 = $pts[2];
		$y2 = $pts[3];
		
		$node->bbox = [$x1, $y1, $x2, $y2];
	}
	
	if (isset($attributes['pageId']))
	{
		$node->pageId = $attributes['pageId'];
	}
	
	return $node;
}


//----------------------------------------------------------------------------------------
function plazi_to_html($filename, $page_images = array())
{
	$xml = file_get_contents($filename);
	
	$show_html = false;
	$show_html = true;

	$dom= new DOMDocument;
	$dom->loadXML($xml);
	$xpath = new DOMXPath($dom);
		
	$pages = array();
	
	foreach($xpath->query ('//treatment') as $treatment)
	{
		foreach ($xpath->query ('subSubSection', $treatment) as $subSubSection)
		{
			
			foreach ($xpath->query ('paragraph', $subSubSection) as $paragraph)
			{
				$node = get_common_node_details($paragraph);
				
				//print_r($node);
				
				if (isset($node->pageId))
				{
					if (!isset($pages[$node->pageId]))
					{
						$pages[$node->pageId] = array();
					}
					$pages[$node->pageId][$node->id] = $node;
				}
			}
			
		
		}
		
		foreach ($xpath->query ('//caption', $treatment) as $caption)
		{
			$node = get_common_node_details($caption);
			//print_r($node);
			
			if (isset($node->pageId))
			{
				if (!isset($pages[$node->pageId]))
				{
					$pages[$node->pageId] = array();
				}
				$pages[$node->pageId][$node->id] = $node;
			}		
		}

		foreach ($xpath->query ('//figureCitation', $treatment) as $caption)
		{
			$node = get_common_node_details($caption);
			//print_r($node);
			
			if (isset($node->pageId))
			{
				if (!isset($pages[$node->pageId]))
				{
					$pages[$node->pageId] = array();
				}
				$pages[$node->pageId][$node->id] = $node;
			}		
		}
		
		
		

	}
 

 	ksort($pages, SORT_NUMERIC);
 	
 	
 	if (0)
 	{
	 	print_r($pages);
		exit();
	}
 	
 	if ($show_html)
 	{
		echo '<html>
		<head>
		<style>
			body {
				background:rgb(228,228,228);
				margin:0;
				padding:0;
				
			}
		
			.page {
				background-color:white;
				position:relative;
				margin: 0 auto;
				/* border:1px solid black; */
				margin-bottom:1em;
				margin-top:1em;	
				box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
			}
		
			/* paragraph */
			.paragraph {
				position:absolute;
				background-color:rgba(255,255,0,0.2);
				border:1px solid rgba(192,192,192);
			}
			
			/* block */
			.block {
				position:absolute;
				background-color:rgba(255,255,0,0.2);
				/*border:1px solid rgba(192,192,192);*/
			}
			
		
			/* figure */
			.image {
				position:absolute;
				background-color:rgba(255,53,184,0.2);
				/* Can invert image if needed https://stackoverflow.com/a/13325820/9684 */
				/* filter: invert(1); */
			}
			
			.caption {
				position:absolute;
				background-color:rgba(255,53,184,0.2);
				/* Can invert image if needed https://stackoverflow.com/a/13325820/9684 */
				/* filter: invert(1); */
			}
			
		
			/* table */
			.table {
				position:absolute;
				background-color:rgba(0,0,255,0.2);
			}
		
			/* line */
			.line {
				position:absolute;
				background-color:green;
				opacity:0.4;
			}
		
			/* visible text */
			.token {
				position:absolute;
			}	
		
			.token-text {
				rgba(19,19,19,1);
				vertical-align:baseline;
				white-space:nowrap;
			}	
	
		</style>
		</head>
		<body>
		<div>';	 	
 	
		$scale = 700 / $page_images[0]->width;
		
		$n = count($page_images);
		
		for ($page_id = 0; $page_id < $n; $page_id++)
		{
			echo '<div class="page" style="position:relative;'
				. 'width:' . ($scale * $page_images[$page_id]->width) . ';'
				. 'height:' . ($scale * $page_images[$page_id]->height) . ';'
				. '">';
				
			echo '<img src="' . $page_images[$page_id]->path . '" width="700">';
			// echo $page->html;
			
			if (isset($pages[$page_id]))
			{
	
				foreach ($pages[$page_id] as $node_id => $node)
				{
				
					// print_r($node);
				
					if (isset($node->bbox))
					{
						$left   = $scale * $node->bbox[0];
						$top    = $scale * $node->bbox[1];
						$width  = $scale * ($node->bbox[2] - $node->bbox[0]);
						$height = $scale * ($node->bbox[3] - $node->bbox[1]);
					
						
						echo '<div id="' . $node_id . '" class="' . $node->type . '" style="'
						. 'left:' . $left . 'px;top:' . $top. 'px;width:' . $width . 'px;height:' . $height . 'px;">';					
						//echo $paragraph->text;					
						echo '</div>';
					}
				}
			}
			
			echo '</div>';
		
		}
		
		echo '</div>';
		echo '</body></html>';
	}
	
	
	
}


//----------------------------------------------------------------------------------------
function plazi_to_json($filename)
{
	$obj = new stdclass;

	$obj = new stdclass;
	$obj->pages = array();
	$obj->text_bbox = new BBox(0,0,0,0);

	$image_counter = 1;

	$page_count = 0;
	
	$line_counter = 0;
	

	$xml = file_get_contents($filename);

	$dom= new DOMDocument;
	$dom->loadXML($xml);
	$xpath = new DOMXPath($dom);
	
	$bbox = new BBox(0, 0, 0, 0);
	
	$pages = array();

		
	foreach($xpath->query ('//treatment') as $treatment)
	{
		foreach ($xpath->query ('subSubSection', $treatment) as $subSubSection)
		{
						
			foreach ($xpath->query ('paragraph', $subSubSection) as $paragraph)
			{			
			
				$attributes = array();
				$attrs = $paragraph->attributes; 
			
				foreach ($attrs as $i => $attr)
				{
					$attributes[$attr->name] = $attr->value; 
				}
				
				$pageId = -1;
				
				if (isset($attributes['pageId']))
				{
					$pageId = $attributes['pageId'];
					if (!isset($pages[$pageId]))
					{
						$pages[$pageId] = new stdclass;
						$pages[$pageId]->html = '';
						$pages[$pageId]->bbox = new BBox(0, 0, 0, 0);
					}
				}
				
				
				$pts = array();
				
				if (isset($attributes['box']))
				{
					$box = $attributes['box'];
					$box = str_replace('[', '', $box);
					$box = str_replace(']', '', $box);
					$pts = explode(",", $box);
				}
				
				if (count($pts) == 0)
				{
					
					if (isset($attributes['blockId']))
					{
						$box = $attributes['blockId'];
						$box = preg_replace('/^\d+\./', '', $box);
						$box = str_replace('[', '', $box);
						$box = str_replace(']', '', $box);
						$pts = explode(",", $box);
					}
					
				}
					
				if (count($pts) > 0)
				{
					$x1 = $pts[0];
					$x2 = $pts[1];
					$y1 = $pts[2];
					$y2 = $pts[3];
					
					//print_r($pts);
					
					$left = $x1;
					$top = $y1;
					$width = $x2 - $x1;
					$height = $y2 - $y1;
					
					$b = new BBox($x1, $y1, $x2, $y2);
					$bbox->merge($b);
					
					$pages[$pageId]->bbox->merge($b);
					
					
					/*
					$pages[$pageId]->html .= '<div style="position:absolute;background-color:rgba(190,0,0, 0.3);left:' . $left . 'px;top:' . $top . 'px;width:' . $width . 'px;height:' . $height . 'px;">';					
					$pages[$pageId]->html .=  $paragraph->textContent;					
					$pages[$pageId]->html .=  '</div>';
					*/
				}

				
				
				// figure
				foreach ($xpath->query ('figureCitation', $paragraph) as $figureCitation)
				{
					$attributes = array();
					$attrs = $figureCitation->attributes; 
			
					foreach ($attrs as $i => $attr)
					{
						$attributes[$attr->name] = $attr->value; 
					}
				
					//echo $attributes['box'] . "\n";
				
					if (isset($attributes['box']))
					{
						$box = $attributes['box'];
						$box = str_replace('[', '', $box);
						$box = str_replace(']', '', $box);
						$pts = explode(",", $box);
					
						$x1 = $pts[0];
						$x2 = $pts[1];
						$y1 = $pts[2];
						$y2 = $pts[3];
					
						//print_r($pts);
					
						$left = $x1;
						$top = $y1;
						$width = $x2 - $x1;
						$height = $y2 - $y1;
						
						$b = new BBox($x1, $y1, $x2, $y2);
						$bbox->merge($b);
					
						$pages[$pageId]->bbox->merge($b);
					
						/*
						
						$pages[$pageId]->html .=  '<div style="position:absolute;background-color:rgba(0,190,0, 0.3);left:' . $left . 'px;top:' . $top . 'px;width:' . $width . 'px;height:' . $height . 'px;">';
					
						$pages[$pageId]->html .=  $figureCitation->textContent;
					
						$pages[$pageId]->html .=  '</div>';
						*/
						
					}
					
					if (isset($attributes['captionTargetBox']))
					{
						$box = $attributes['captionTargetBox'];
						$box = str_replace('[', '', $box);
						$box = str_replace(']', '', $box);
						$pts = explode(",", $box);
					
						$x1 = $pts[0];
						$x2 = $pts[1];
						$y1 = $pts[2];
						$y2 = $pts[3];
					
						//print_r($pts);
					
						$left = $x1;
						$top = $y1;
						$width = $x2 - $x1;
						$height = $y2 - $y1;
						
						$b = new BBox($x1, $y1, $x2, $y2);
						
						
						$pages[$pageId]->bbox->merge($b);
						
						$captionTargetPageId = $attributes['captionTargetPageId'];
						if (!isset($pages[$captionTargetPageId]))
						{
							$pages[$captionTargetPageId] = new stdclass;
							$pages[$captionTargetPageId]->html = '';
							$pages[$captionTargetPageId]->bbox = new BBox(0, 0, 0, 0);
						}
						
						$pages[$captionTargetPageId]->bbox->merge($b);
					
						/*
						$pages[$captionTargetPageId]->html .=  '<div style="position:absolute;left:' . $left . 'px;top:' . $top . 'px;width:' . $width . 'px;height:' . $height . 'px;">';
						//$pages[$captionTargetPageId]->html .=  '<img src="' . $attributes['httpUri'] . '" width="' . $width . '">';					
						$pages[$captionTargetPageId]->html .=  '</div>';
						*/
						
					}					
				}
				
				
				// bibRefCitation
				foreach ($xpath->query ('bibRefCitation', $paragraph) as $bibRefCitation)
				{
					$attributes = array();
					$attrs = $bibRefCitation->attributes; 
			
					foreach ($attrs as $i => $attr)
					{
						$attributes[$attr->name] = $attr->value; 
					}
				
					//echo $attributes['box'] . "\n";
				
					if (isset($attributes['box']))
					{
						$box = $attributes['box'];
						$box = str_replace('[', '', $box);
						$box = str_replace(']', '', $box);
						$pts = explode(",", $box);
					
						$x1 = $pts[0];
						$x2 = $pts[1];
						$y1 = $pts[2];
						$y2 = $pts[3];
					
						//print_r($pts);
					
						$left = $x1;
						$top = $y1;
						$width = $x2 - $x1;
						$height = $y2 - $y1;
						
						$b = new BBox($x1, $y1, $x2, $y2);
						$bbox->merge($b);
					
						$pages[$pageId]->bbox->merge($b);
						
						/*
						$pages[$pageId]->html .=  '<div style="position:absolute;background-color:rgba(0,190,0, 0.3);left:' . $left . 'px;top:' . $top . 'px;width:' . $width . 'px;height:' . $height . 'px;">';
					
						$pages[$pageId]->html .=  $bibRefCitation->textContent;
					
						$pages[$pageId]->html .=  '</div>';
						*/
					}	
				}			
				
				// emphasis
				foreach ($xpath->query ('taxonomicName/emphasis', $paragraph) as $emphasis)
				{
					$attributes = array();
					$attrs = $emphasis->attributes; 
			
					foreach ($attrs as $i => $attr)
					{
						$attributes[$attr->name] = $attr->value; 
					}
				
					//echo $attributes['box'] . "\n";
				
					if (isset($attributes['box']))
					{
						$box = $attributes['box'];
						$box = str_replace('[', '', $box);
						$box = str_replace(']', '', $box);
						$pts = explode(",", $box);
					
						$x1 = $pts[0];
						$x2 = $pts[1];
						$y1 = $pts[2];
						$y2 = $pts[3];
					
						//print_r($pts);
					
						$left = $x1;
						$top = $y1;
						$width = $x2 - $x1;
						$height = $y2 - $y1;
						
						$b = new BBox($x1, $y1, $x2, $y2);
						$bbox->merge($b);
					
					
						$pages[$pageId]->bbox->merge($b);
						
						/*
						
						$pages[$pageId]->html .=  '<div style="position:absolute;background-color:rgba(0,190,0, 0.3);left:' . $left . 'px;top:' . $top . 'px;width:' . $width . 'px;height:' . $height . 'px;">';
					
						$pages[$pageId]->html .=  $emphasis->textContent;
					
						$pages[$pageId]->html .=  '</div>';
						*/
						}	
				}							
				
				
				// geoCoordinate
				foreach ($xpath->query ('geoCoordinate', $paragraph) as $geoCoordinate)
				{
					$attributes = array();
					$attrs = $geoCoordinate->attributes; 
			
					foreach ($attrs as $i => $attr)
					{
						$attributes[$attr->name] = $attr->value; 
					}
				
					//echo $attributes['box'] . "\n";
				
					if (isset($attributes['box']))
					{
						$box = $attributes['box'];
						$box = str_replace('[', '', $box);
						$box = str_replace(']', '', $box);
						$pts = explode(",", $box);
					
						$x1 = $pts[0];
						$x2 = $pts[1];
						$y1 = $pts[2];
						$y2 = $pts[3];
					
						//print_r($pts);
					
						$left = $x1;
						$top = $y1;
						$width = $x2 - $x1;
						$height = $y2 - $y1;
						
						$b = new BBox($x1, $y1, $x2, $y2);
						$bbox->merge($b);
					
						$pages[$pageId]->bbox->merge($b);
						
						/*
						
						$pages[$pageId]->html .=  '<div style="position:absolute;background-color:rgba(0,190,0, 0.3);left:' . $left . 'px;top:' . $top . 'px;width:' . $width . 'px;height:' . $height . 'px;">';
					
						$pages[$pageId]->html .=  $geoCoordinate->textContent;
					
						$pages[$pageId]->html .=  '</div>';
						*/
	
					}	
				}							
								
				
				
				
		
			}
			
		
		}
	}
	
	// bounding box for all boxes
	//echo $bbox->toHtml();
	
	
	//echo '</div>';
	//echo '</html>';
	
 	
 
 	ksort($pages, SORT_NUMERIC);
 	
 	print_r($pages);
 	
 	$bbox = new BBox(0, 0, 0, 0);
	foreach ($pages as $k => $page)
	{
		$bbox->merge($page->bbox);
	}
	
	/*
	
	foreach ($pages as $k => $page)
	//$k = 9;
	//$page = $pages[$k];
	{
		
		//echo '<p>' . $k . '</p>';
		
		echo '<div style="position:relative;'
		//	. 'width:' . $bbox->maxx . 'px;'
		//	. 'height:' . $bbox->maxy . 'px;'
			. 'width:1571;'
			. 'height:2222;'
			
			. 'background-image: url(\'page-' . str_pad(($k+1), 6, '0', STR_PAD_LEFT) . '.png\');'
			
			. 'border:1px solid black;">';
		echo $page->html;
		//echo '<img src="page-000034.png">';
		echo '</div>';
		
		//echo '<p>Break</p>';
	
	}
	*/	
	
	
}



// test
if (1)
{
	
	$filename = '03F587E2B22862643686FDEF53E2F87E.xml';
	
	// load and get image sizes
	$images = array();
	$dir = 'images';
	
	$files = scandir($dir);
	foreach ($files as $image_filename)
	{
		if (preg_match('/page-(\d+)\./', $image_filename, $m))
		{
			$page_num = (Integer)$m[1];
			
			$image = new stdclass;
			$image->path = $dir . '/' . $image_filename;
			
			$wh = getimagesize($dir . '/' . $image_filename);
			$image->width = $wh[0];
			$image->height = $wh[1];
			
			$images[] = $image;
		}
	}	
	
	plazi_to_html($filename, $images);
}



?>
