<?php
require_once "auth.php";
require_once "db.php";

$db = new DatabaseWriter();

/** @var string $path */
/** @var Pet $pet */
$pet = null;
if (isset($path)) {
	$pet = $db->getPetByPath($path);
} else {
	if (@isset($_GET["id"])) {
		$pet = $db->getPetById($_GET["id"]);
	}
}
if ($pet === null && isset($expectListing) && $expectListing) {
	return; // this is not a valid listing, so go back to handler.php if applicable
}

if (@isset($_POST["id"])) {
	// TODO: Add or update a listing
}

// TODO: listing editor
?>
	<!DOCTYPE html>
	<title><?=$pet ? htmlspecialchars($pet->id) . ' ' . htmlspecialchars($pet->name) : 'Listing Editor'?></title>
	<meta charset="UTF-8">
<?php
style();
?>

	<form method="POST">
		<ul>
			<li class="name">
				<label for="name">Name</label>
				<input type="text" name="name" id="name" value="<?=$pet ? htmlspecialchars($pet->name) : ''?>" required>
			<li class="species">
				<label for="species">Species</label>
				<select name="species" id="species" required>
					<option value=""></option>
					<?php
					foreach (_G_species() as $species) {
						/** @var Species $species */
						echo '<option value="' . $species->key . '"';
						if ($pet && $species === $pet->species) {
							echo ' selected';
						}
						echo '>' . ucfirst($species->name) . '</option>';
					}
					?>
				</select>
			<li class="breed">
				<label for="breed">Breed</label>
				<input type="text" name="breed" id="breed" value="<?=$pet ? htmlspecialchars($pet->breed) : ''?>">
			<li class="dob">
				<label for="dob"><abbr title="date of birth">DOB</abbr></label>
				<input type="date" name="dob" id="dob" value="<?=$pet ? $pet->dob : ''?>" required>
			<li class="sex">
				<fieldset>
					<legend>Sex</legend>
					<?php foreach (_G_sexes() as $sex): ?>
						<input type="radio" name="sex" value="<?=$sex->key?>" id="sex_<?=$sex->key?>"
							<?php
							if ($pet && $pet->sex === $sex) {
								echo ' selected';
							}
							?> required>
						<label for="sex_<?=$sex->key?>">
							<abbr title="<?=$sex->name?>"><?=strtoupper($sex->name[0])?></abbr>
						</label>
					<?php endforeach; ?>
				</fieldset>
			<li class="fee">
				<label for="fee">Fee</label>
				<input type="text" name="fee" id="fee" value="<?=htmlspecialchars($pet ? $pet->fee : '$')?>">
			<li class="status">
				<label for="status">Status</label>
				<select name="status" id="status" required>
					<option value=""></option>
					<?php
					foreach (_G_statuses() as $status) {
						/** @var Status $status */
						echo '<option value="' . $status->key . '"';
						if ($pet && $status === $pet->status) {
							echo ' selected';
						}
						echo '>';
						echo $status->name;
						echo '</option>';
					}
					?>
				</select>
		</ul>
	</form>
<?=null?>

<?php
exit(0); // Exit from handler.php if applicable