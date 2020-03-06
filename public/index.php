<?php
require_once("../src/common.php");
require_once("$t/logo.php");
require_once("$t/donate.php");
$transportDate = strtotime(_G_transport_date());
?>
<!DOCTYPE html>
<title><?=_G_longname()?></title>
<?php style(); ?>
<header>
	<nav>
		<h1><?php logo(); ?></h1>
		<?php donate(); ?>
		<a href="https://www.facebook.com/ForgetMeNotAnimalShelter/">Facebook</a>
		<a href="/blog">Blog</a>
		<form id="adopt" action="/application" method="POST">
			<label for="adopt_button">Adopt a Pet</label>
			<button id="adopt_button" type="submit">Apply Online Now</button>
		</form>
	</nav>
</header>
<section id="listings">
	<h2>Adoptable pets</h2>
	<a href="/cats">See our Cats &amp; Kittens</a>
	<a href="/dogs">See our Dogs &amp; Puppies</a>
</section>
<section id="transport">
	<h2>Transport dates</h2>
	<h3>Next Seattle area transport</h3>
	<p><time datetime="<?=date("Y-m-d")?>"><?=date("M j")?></time> (Monroe)
	<h3>Next Spokane transport</h3>
	<p>frequent, flexible
</section>