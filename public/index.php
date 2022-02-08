<?php
require_once "../src/common.php";
require_once "$t/header.php";
require_once "$t/footer.php";
require_once "$src/db.php";
require_once "$src/pet.php";
$transportDate = strtotime(_G_transport_date());
$db ??= new Database();
?>
<!DOCTYPE html>
<html lang="en-US">
<title><?=_G_longname()?></title>
<meta charset="utf-8">
<script src="/email.js.php"></script>
<?php
style();
pageHeader();
?>
<section id="listings">
	<h2>Adoptable pets</h2>
	<ul>
		<?php
		$displayedSpecies = 0;
		foreach ($db->getAllSpecies() as $species) { // TODO [#17]: cache?
		/* @var $species Species */
		if ($species->species_count):
		$displayedSpecies++; ?>
		<li><a href="/<?=$species->plural()?>">See our <?=$species->pluralWithYoung()?></a>
			<?php else: ?>
				<!-- Found zero adoptable <?=$species->plural()?> -->
			<?php endif;
			}
			if ($displayedSpecies === 0): ?>
		<li>There are currently no adoptable pets! Please check back later.
			<?php endif; ?>
	</ul>
</section>
<section id="transport">
	<h2>Transport dates</h2>
	<h3>Next Seattle area transport</h3>
	<p>
		<time datetime="<?=date("Y-m-d", $transportDate)?>"><?=date("M j", $transportDate)?></time>
		(Monroe)
	<h3>Next Spokane transport</h3>
	<p>frequent, flexible
</section>
<?php footer(); ?>
</html>
