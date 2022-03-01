<?php
header("HTTP/1.0 500 Internal Server Error");
?>
	<!DOCTYPE html>
	<title>500 Internal Server Error</title>
	<style>
		html {
			display: flex;
			width: 100%;
			height: 100%;
			box-sizing: border-box;
			margin: 0;
			justify-content: center;
			align-items: center;
			background-color: #000;
			text-align: center;
		}
	</style>
	<img src="//http.cat/500" alt="500 Internal Server Error">
	<form action="/" method="POST">
		<button type="submit">Return to the shelter homepage</button>
	</form>
<?php
include_once __DIR__ . "/../common.php";
log_err("500 error at path " . $path ?? $_SERVER["REQUEST_URI"]);
exit(500);
