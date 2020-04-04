<?php
header("HTTP/1.0 500 Internal Server Error");
?>
	<!DOCTYPE html>
	<title>500 Internal Server Error</title>
	<h1>500 Internal Server Error</h1>
	<p>:(
		<!-- TODO: 500 page -->
<?php
include_once __DIR__ . "/../common.php";
log_err("500 error at path " . $path ?? $_SERVER["REQUEST_URI"]);
exit(500);