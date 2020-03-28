<?php
require_once __DIR__ . "/../common.php";
header("HTTP/1.0 510 Not Extended");
?>
<!DOCTYPE html>
<title>510 Not Extended</title>
<h1>510 Not Extended</h1>
<p>:(
<!-- TODO: 510 page -->
<?php
log_err("510 error at path " . $path ?? $_SERVER["REQUEST_URI"]);
exit(510);