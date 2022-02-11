<?php
// Caching
$etag = '"' . filemtime(__FILE__) . '"';
header("ETag: $etag");
$ifNoneMatch = array_map('trim', explode(',', trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')));
if (in_array($etag, $ifNoneMatch, true)) {
	header("HTTP/2 304 Not Modified");
	exit();
}

header("Content-Type: image/svg+xml");
?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 5 5">
	<path d="M2 0H3V2H5V3H3V5H2V3H0V2H2" fill="#<?=$_GET["color"]?>"/>
</svg>
