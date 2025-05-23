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
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="canonical" href="https://<?=_G_public_domain()?>">
<script src="/email.js.php"></script>
<?php
style();
style("home");
pageHeader();
?>
<div class="home">
	<article>
		<aside class="warning">
			<strong>All shelter visits for any reason must be done by appointment only.</strong>
			<p><a href="tel:<?=_G_phone_intl()?>">Call</a> or <a data-email="info">email</a> to make an appointment for a
				day/time that is convenient for you.
		</aside>
		<aside class="info">
			<h3>Lost a pet? Found a pet?</h3>
			<p><strong>Check our <a href="https://www.facebook.com/ForgetMeNotAnimalShelter/">Facebook page</a> for recent
					lost/found listings.</strong>
			<p>To have your lost/found listed, please <a data-email="info">email us</a>, with photos if possible.</p>
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

				<li><a href="/<?=$species->plural()?>" draggable="false">
						<h3>See our <?=$species->pluralWithYoung()?></h3>
						<img src="/assets/<?=$species->plural()?>.jpg" alt="<?=$species->plural()?>">
					</a>
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
			<p><strong>Can't come to Republic? Don't let that stop you!</strong>
				<br>We have monthly transports to the Seattle area, delivering adopted pets to their new owners.
				If you can add a little to your donation to help us cover our volunteer driver's expenses, that would be great!
			<section class="seattle">
				<h3>Next Seattle area transport</h3>
				<?php if (date("Y-m-d") <= '2024-06-29'): ?>
				<p>
					<time datetime="2024-06-29">Jun 29, 2024</time>
					(N Tacoma)
					<?php endif; ?>
				<p>
					<time datetime="<?=date("Y-m-d", $transportDate)?>"><?=$transportDate <= strtotime('today') ? 'TBD' :
								date("M j, Y", $transportDate)?></time>
					(Monroe)
			</section>
		</section>
	</article>
	<aside class="left">
		<h2>Programs</h2>
		<form action="/owner-surrender" method="GET">
			<h3><a href="/owner-surrender">Owner Surrenders</a></h3>
			<p><strong>Need to rehome a pet?</strong> Have an unwanted litter of puppies or kittens (8 weeks or younger)? Fill out the request
				for assistance here:<br>
				<button type="submit">Owner Surrender Application</button>
		</form>
		<section>
			<h3>Stop the Cycle<br>Spay/Neuter Vouchers</h3>
			<p>Our Stop the Cycle program has both spay/neuter vouchers to help lower income residents in Ferry County with
				the cost, and a litter rehoming service! With the litter rehoming service, we also provide spay/neuter vouchers
				for the parent cats or dogs, regardless of family income.
			<p>Request voucher assistance before it's too late, OR request a spot in our Stop the Cycle litter program, by
				emailing <a data-email="info"></a> or leaving us a message at <a
						href="tel:<?=_G_phone_intl()?>"><?=_G_phone()?></a>.
			<p>If you want some help, we want to help you.
		</section>
		<section>
			<h3>Fear No Feral<br>Trap&ndash;Neuter&ndash;Return</h3>
			<p>Do you have a colony of feral cats on your property? They are FABULOUS for keeping down vermin populations, but
				without spay/neuter, you could be trading one problem for another. We can help! Our Fear No Feral
				Trap-Neuter-Return program will provide traps, instructions, and pay all costs for spay/neuter, rabies
				vaccination, and "ear-tipping" of the cats in your colony. Email us at <a data-email="info"></a> or leave a
				message for our feral coordinator at <a href="tel:<?=_G_phone_intl()?>"><?=_G_phone()?></a>; let's keep your
				ferals working for you without overwhelming you!
		</section>
	</aside>
	<aside class="right">
		<h2>Get Involved</h2>
		<section>
			<h3>Volunteer</h3>
			<p>We always need <b>LOVE LOVE LOVE</b> for the fuzzballs! Want to brush a cat or walk a dog? We need you! You
				can volunteer as little as 3 hours a month. Call <a href="tel:<?=_G_phone_intl()?>"><?=_G_phone()?></a> or email
				<a
						data-email="info"></a>.
		</section>
		<section class="donate">
			<h3>Donate</h3>
			<p>We are an IRS-approved 501(c)3 charitable organization. Your donations are fully tax-deductible.
			<p><strong>Donate via PayPal</strong><br>(one-time or monthly pledge):
			<form action="https://www.paypal.com/donate" method="post" class="paypal">
				<input type="hidden" name="cmd" value="_s-xclick">
				<input type="hidden" name="hosted_button_id" value="EYQVKDS74KYZ2">
				<input type="image" src="/assets/btn_donateCC_LG.gif" name="submit"
						alt="Donate through PayPal">
			</form>
			<p><strong>Donate via Network for Good</strong><br>(one-time or monthly pledge):<br>
				<a href="https://www.networkforgood.org/donation/ExpressDonation.aspx?ORGID2=91-1996344">
					<img src="/assets/networkforgoodlogo.gif" alt="Network for Good">
				</a>
		</section>
		<section class="adopted">
			<figure>
				<figcaption>
					Want to see updates on our already adopted pets?
					<br>Check our
					<a href="https://www.facebook.com/groups/135175210176154/" title="Adopted Pets">
						Adopters and Supporters Facebook Group</a>!
				</figcaption>
				<a href="https://www.facebook.com/groups/135175210176154/" title="Adopted Pets"><img alt="Adopted Pets"
							src="/assets/adopted.jpg"></a>
			</figure>
		</section>
	</aside>
</div>
<footer>
	<div class="f990 noprint">
		View our IRS Form 990:
		<ul>
			<li><a href="2021990.pdf">2021</a></li>
			<li><a href="2020990.pdf">2020</a></li>
			<li><a href="2019990forwebsite.pdf">2019</a></li>
			<li><a href="2018990website.pdf">2018</a></li>
			<li><a href="2017website990.pdf">2017</a></li>
			<li><a href="2016990.pdf">2016</a></li>
			<li><a href="2015990.pdf">2015</a></li>
			<li><a href="2014990.pdf">2014</a></li>
			<li><a href="2013990.pdf">2013</a></li>
			<li><a href="2012990.pdf">2012</a></li>
			<li><a href="2011990.pdf">2011</a></li>
			<li><a href="2010990complete.pdf">2010</a></li>
			<li><a href="2009990comp.pdf">2009</a></li>
			<li><a href="2008990.pdf">2008</a></li>
			<li><a href="2007990.pdf">2007</a></li>
			<li><a href="2006990complete.pdf">2006</a></li>
			<li><a href="2005990.pdf">2005</a></li>
		</ul>
	</div>
	<aside class="logos noprint">
		<ul>
			<li><a href="//wafederation.org"><img src="/assets/WAFed.png"
							alt="2019 Member - The Washington Federation of Animal Care and Control Agencies" class="darken"></a></li>
			<li>Thanks to Petfinder:<br><a href="//petfinder.com/videos"><img src="/assets/pet-videos.gif"
							alt="Be a responsible pet parent - train your pet!"></a><br>Be a responsible pet parent &ndash; train your
				pet!
			</li>
			<li><a href="//hillspet.com/products/science-diet.html"><img src="/assets/hills.jpg"
							alt="We feed and recommend Hill's Science Diet."
							title="We feed HILL'S SCIENCE DIET exclusively.This premium diet is made possible by the generosity of Hill's Science Diet, and we thank them for their support."></a>
			</li>
			<li><a href="//adoptapet.com"><img
							src="https://images-origin.adoptapet.com/images/shelter-badges/Approved-Shelter_Blue-Badge.png"
							alt="Adopt-a-Pet.com Approved Shelter"></a></li>
		</ul>
	</aside>
	<script src="f990.js"></script>
	<?php footer(); ?>
</footer>
</html>
