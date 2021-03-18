<?php
require_once "auth.php";

$error = false;
if (@isset($_POST["set_date"])) {
	require_once "db.php";
	$dbw ??= new DatabaseWriter();

	$transportDate = DateTime::createFromFormat("Y-m-d", $_POST["set_date"]);
	if (!($transportDate && $transportDate->format("Y-m-d") == $_POST["set_date"])) {
		// date failed Y-m-d validation
		$error = "Received date {$_POST["set_date"]} is not in YYYY-MM-DD format";
	} elseif ($_POST["set_date"] != _G_transport_date() && $error = $dbw->setTransportDate($_POST["set_date"])) {
		$error = "Failed to set transport date: $error";
	} else {
		header("Location: http://$_SERVER[HTTP_HOST]/");
		exit();
	}
}

// Current transport date
$transportDate = strtotime(_G_transport_date());
?>
<!DOCTYPE html>
<title>Update Transport Date</title>
<meta charset="UTF-8">
<?php
style();
?>

<a href="/" id="back">Back to admin home page</a>

<?php if($error): ?>
<aside class="error"><?=$error?></aside>
<?php endif; ?>

<form method="POST">
	<label for="date">
		Transport date:
		<input type="date" name="set_date" value="<?=date("Y-m-d", $transportDate)?>" min="<?=date("Y-m-d", strtotime("2 days ago"))?>">
	</label>
	<br>
	<input type="submit" value="Update">
</form>