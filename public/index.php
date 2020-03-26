<?php
require_once("../src/common.php");
require_once("$t/logo.php");
require_once("$t/donate.php");
require_once("$t/adopt_button.php");
require_once("$src/db.php");
require_once("$src/pet.php");
$transportDate = strtotime(_G_transport_date());
$db            = new Database();
?>
<!DOCTYPE html>
<title><?=_G_longname()?></title>
<?php style(); ?>
<header>
	<nav>
		<h1><?php logo(); ?></h1>
		<?php donate(); ?>
		<ul>
			<li><a href="https://www.facebook.com/ForgetMeNotAnimalShelter/">Facebook</a>
			<li><a href="/blog">Blog</a>
		</ul>
		<?php adopt_button(); ?>
	</nav>
</header>
<section id="listings">
	<h2>Adoptable pets</h2>
	<ul>
		<?php
		foreach ($db->getAllSpecies() as $species) {
			/* @var $species Species */
			if ($species->__get("species_count")): ?>
			<li><a href="/<?=$species->__get("plural")?>">See our <?=ucfirst($species->__get("plural"))?>
					&amp; <?=ucfirst($species->__get("young_plural"))?></a>
			<?php else: ?>
				<!-- Found zero adoptable <?=$species->__get("plural")?> -->
			<?php endif;
		} ?>
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