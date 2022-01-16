<?php
include_once "../src/common.php";
header("HTTP/1.0 404 Not Found");
?>
    <!DOCTYPE html>
    <title>404 Not Found</title>
    <h1>404 Not Found</h1>
    <p>:(
    <!-- <?=$_GET["p"]?> -->
<?php
log_err("404 error at path " . $_GET["p"]);
exit(404);