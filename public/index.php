<?php
require_once("../src/common.php");
require_once("$t/logo.php");
require_once("$t/donate.php");
require_once("$t/adopt_button.php");
$transportDate = strtotime(_G_transport_date());
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
		<li><a href="/cats">See our Cats &amp; Kittens</a>
		<li><a href="/dogs">See our Dogs &amp; Puppies</a>
	</ul>
</section>
<section id="transport">
	<h2>Transport dates</h2>
	<h3>Next Seattle area transport</h3>
	<p>
		<time datetime="<?=date("Y-m-d")?>"><?=date("M j")?></time>
		(Monroe)
	<h3>Next Spokane transport</h3>
	<p>frequent, flexible
</section>