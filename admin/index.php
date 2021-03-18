<?php
require_once "../src/common.php";
$transportDate = strtotime(_G_transport_date());
?>
<!DOCTYPE html>
<title>FMNAS Admin</title>
<meta charset="UTF-8">
<meta name="robots" content="none">
<?php
style();
?>

<p>What do you want?
<ul>
	<li><a href="listings.php">Update pet listings</a>
	<li><a href="templates.php">Manage listing templates</a>
	<li><a href="transport_date.php">Update transport date (currently: <?=$transportDate?>)</a>
	<!-- TODO:
	<li><a href="metadata.php">Update site metadata</a>
	<li><a href="audit_log.php">View audit log and revert changes</a>
	<li><a href="logout.php">Log out</a>
	-->