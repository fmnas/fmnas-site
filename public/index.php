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
style('home');
pageHeader();
?>
<div class="home">
	<aside class="left">
		<h2>Programs</h2>
		<section>
			<h3>Lost a pet?<br>Found a pet?</h3>
			TODO: Lost/found section
		</section>
		<section>
			<h3>Owner Surrenders</h3>
			TODO: Owner surrender section
		</section>
		<section>
			<h3>Stop the Cycle<br>Spay/Neuter Vouchers</h3>
			TODO: Stop the cycle section
		</section>
		<section>
			<h3>Fear No Feral</h3>
			TODO: Fear No Feral section
		</section>
	</aside>
	<article>
		<aside class="warning">
			All shelter visits by appointment only
			TODO: Flesh out the warning
		</aside>
		<section class="listings">
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
		<section class="transports">
			<h2>Transport dates</h2>
			<p>Can't come to Republic?</p>
			<section class="seattle">
				<h3>Next Seattle area transport</h3>
				<p>
					<time datetime="<?=date("Y-m-d", $transportDate)?>"><?=date("M j, Y", $transportDate)?></time>
					(Monroe)
			</section>
			<section class="spokane">
				<h3>Spokane transports</h3>
				<p>frequent &amp; flexible</p>
			</section>
		</section>
	</article>
	<aside class="right">
		<h2>Get Involved</h2>
		<section>
			<h3>Volunteer</h3>
			TODO: Volunteer section.
		</section>
		<section class="donate">
			<h3>Donate</h3>
			TODO: Donate section.
		</section>
		<section class="adopted">
			<h3>Want updates on adopted pets?</h3>
			TODO: Adopted section.
		</section>
		<section class="share">
			<h3>Share</h3>
			TODO: Share buttons.
		</section>
	</aside>
</div>
<footer>
	<aside class="logos">
		TODO: Logos.
	</aside>
	<?php footer(); ?>
</footer>
</html>
