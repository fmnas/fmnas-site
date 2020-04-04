<?php
require_once "../src/common.php";
require_once "$src/pet.php";
require_once "$src/db.php";
require_once "$src/assets.php";
require_once "$t/header.php";
require_once "$t/footer.php";
/* @var $path string */
/* @var $species Species */
$db ??= new Database();
if (!($pet = $db->getPetByPath($path)) || $pet->status->deleted) {
	return; // this is not a valid listing
}
?>
<!DOCTYPE html>
<title>
	<?=htmlspecialchars($pet->name)?>, <?=$pet->species()?>
	<?=$pet->status->listed ? "for adoption at" : ($pet->status->name == _G_statuses()[1]->name ? "adopted from" : "-")?>
	<?=_G_longname()?>
</title>
<meta charset="utf-8">
<?php
	style();
	emailLinks();
	pageHeader();
?>
<h1><?=htmlspecialchars($pet->id . " " . $pet->name)?></h1>
<?php foreach ($pet->photos as $photo) {
	/* @var $photo Asset */
	echo $photo->imgTag(null, true, false);
}
if ($pet->description !== null) {
	echo $pet->description->parse();
}
footer();
exit(0); // Exit afterwards if this is indeed a listing