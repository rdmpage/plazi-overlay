<?php

$filename 		= 'document.pdf';
$dpi 			= 190;
$dpi 			= 192;
$image_format 	= 'png';
$dir 			= 'images';

$command = "pdftoppm -$image_format -r $dpi $filename $dir/page";
echo $command . "\n";
system($command);

echo "Rename images...\n";

$files = scandir($dir);
foreach ($files as $image_filename)
{
	if (preg_match('/page-(\d+)\./', $image_filename, $m))
	{
		$page_num = (Integer)$m[1];
		$page_num--;
	
		$newfilename = 'page-' . str_pad($page_num, 3, '0', STR_PAD_LEFT) . '.' . $image_format;
	
		rename($dir . '/' . $image_filename, $dir . '/' . $newfilename);
	}
}

?>

