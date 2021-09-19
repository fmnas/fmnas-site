<?php
header("Content-Type: image/svg+xml");
$seconds_to_cache = 31536000;
$ts               = gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT";
header("Expires: $ts");
header("Pragma: cache");
header("Cache-Control: max-age=$seconds_to_cache");
?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 5 5">
    <path d="M2 0H3V2H5V3H3V5H2V3H0V2H2" fill="#<?=$_GET["color"]?>"/>
</svg>