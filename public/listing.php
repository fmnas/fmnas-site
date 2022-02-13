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
if (!($pet = $db->getPetByPath($path))) {
	return; // this is not a valid listing
}
?>

	<!DOCTYPE html>
	<html lang="en-US">
	<title>
		<?=htmlspecialchars($pet->name)?>, <?=$pet->species()?>
		<?=$pet->status->listed ? "for adoption at" :
				($pet->status->name == _G_statuses()[1]->name ? "adopted from" : "-")?>
		<?=_G_longname()?>
	</title>
	<meta charset="utf-8">
	<meta name="robots" content="<?=$pet->status->listed ? "index" : "noindex"?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="/email.js.php"></script>
	<?php
	style();
	style("listing", false, "20220208");
	emailLinks();
	pageHeader();
	?>
	<a class="return" href="/<?=$pet->species->plural()?>" title="<?=ucfirst($pet->species->plural())?>">
		Return to the <?=$pet->species->pluralWithYoung()?> page
	</a>
	<article class="listing">
		<h2><?=$pet?></h2>
		<p class="subtitle"><?php
			echo $pet->age();
			echo '&nbsp;&middot;&nbsp;';
			echo $pet->status->name;
			if ($pet->status->listed && !$pet->status->displayStatus) {
				echo '&nbsp;&middot;&nbsp;';
				echo $pet->fee;
			}
			?>
		<aside class="images">
			<?php foreach ($pet->photos as $photo) {
				if ($photo === null) {
					break;
				}
				/* @var $photo Asset */
				echo $photo->imgTag(null, true, false, 480);
			}
			?>
		</aside>
		<?php
		if ($pet->description !== null) {
			echo '<section id="description">';
			echo $pet->description->parse((array) $pet);
			echo '</section>';
		}
		?>
		<form action="/application" method="GET" id="bottom_form">
			If you would like to more about, or think you might like to adopt, <?=$pet->name?>,
			<button>Apply Online Now</button> or email us at: <a data-email="adopt+<?=$pet->id?>">Adopt <?=$pet->name?>!</a>
			<input type="hidden" name="pet" value="<?=$pet->id?>" id="hidden_id">
		</form>
	</article>
	<?php
	footer();
	?>
	<script src="/add_hidden.js"></script>
	</html>
<?php
exit(0); // Exit from handler.php if this is indeed a listing
