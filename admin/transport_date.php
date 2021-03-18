<?php
require_once "../src/common.php";

if ($_POST["set_date"]) {
	// TODO: validate and set transport date
	var_dump($_POST);
	require "$src/generator.php";
	require "$src/generated.php";
	// TODO: verify transport date and redirect home
}

$transportDate = strtotime(_G_transport_date());
?>
<!DOCTYPE html>
<title>Update Transport Date</title>
<meta charset="UTF-8">
<meta name="robots" content="none">
<?php
style();
?>

<a href="/" id="back">Back to admin home page</a>

<form method="POST">
	<label for="date">
		Transport date:
		<input type="date" name="set_date" value="$transportDate" min="<?=date("Y-m-d")?>">
	</label>
</form>