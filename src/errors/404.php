<?php
header("HTTP/1.0 404 Not Found");
?>
<!DOCTYPE html>
<title>404 Not Found</title>
<h1>404 Not Found</h1>
<p>:(
<!-- TODO: 404 page -->
<?php
include_once __DIR__ . "/../common.php";
log_err("404 error at path " . $path ?? $_SERVER["REQUEST_URI"]);
exit(404);