<?php
include("phplib/prepend.php");

$db = new $_ENV["DatabaseClass"];

$t = $db->quote($_REQUEST["t"]);
$f = $db->quote($_REQUEST["f"]);
$v = $db->quote($_REQUEST["v"]);

$sql = "SELECT * FROM image_data WHERE tablename=$t AND fieldname=$f AND keyvalue=$v";
$db->query($sql);
if ($db->next_record()) extract($db->Record);
else {
	if (@$dev) { echo $sql; exit; }
	// write image to disk and analyse to find mime-type if we could be bothered.
	$mimestr="image/jpeg";   // or just guess and hope the browser works it out instead.
	$image_time = "yesterday";
}

$k = $db->quote_identifier($_REQUEST["k"]);
$t = $db->quote_identifier($_REQUEST["t"]);
$f = $db->quote_identifier($_REQUEST["f"]);

$sql = "SELECT $f FROM $t WHERE $k=$v";
$db->query($sql);
if ($db->next_record()) {

	$image_time = strtotime($image_time);
	$send_304 = false;
	if (php_sapi_name() == 'apache') {
		$ar = apache_request_headers();        
		if (isset($ar['If-Modified-Since']) && // If-Modified-Since should exists            
			($ar['If-Modified-Since'] != '') && // not empty            
			(strtotime($ar['If-Modified-Since']) >= $image_time)) // and greater than            
				$send_304 = true; 
	}
	if ($send_304) {        
		// Sending 304 response to browser        
		// "Browser, your cached version of image is OK        
		// we're not sending anything new to you"        
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', $ts).' GMT', true, 304);        
		exit(); // bye-bye    
	}    

	// outputing Last-Modified header    
	header('Last-Modified: '.gmdate('D, d M Y H:i:s', $image_time).' GMT', true, 200);    

	// Set expiration time +1 year    
	// We do not have any photo re-uploading    
	// so, browser may cache this photo for quite a long time    
	header('Expires: '.gmdate('D, d M Y H:i:s',  $image_time + 86400*365).' GMT', true, 200);    

	// outputing HTTP headers    
	header('Content-Length: '.strlen($db->Record[0]));    
	header("Content-type: $mimestr");    

	// outputing image    
	echo $db->Record[0];    
} else {
	if (@$dev) { echo $sql; exit; }
}
?>
