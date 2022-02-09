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
	<article>
		<aside class="warning">
			<strong>All shelter visits for any reason must be done by appointment only.</strong>
			<p>Call or email to make an appointment for a day/time that is convenient for you.
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
						<img src="/assets/<?=$species->plural()?>.jpg" alt="$species->plural()">
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
				<br>We have monthly transports to both the Seattle area and Spokane, delivering adopted pets to their new
				owners.
				If you can add a little to your donation to help us cover our volunteer driver's expenses, that would be great!
			<section class="seattle">
				<h3>Next Seattle area transport</h3>
				<p>
					<time datetime="<?=date("Y-m-d", $transportDate)?>"><?=$transportDate <= strtotime('today') ? 'TBD' :
								date("M j, Y", $transportDate)?></time>
					(Monroe)
			</section>
			<section class="spokane">
				<h3>Spokane transports</h3>
				<p>frequent &amp; flexible</p>
			</section>
		</section>
	</article>
	<aside class="left">
		<h2>Programs</h2>
		<section>
			<h3>Lost a pet?<br>Found a pet?</h3>
			<p><strong>Check our <a href="https://www.facebook.com/ForgetMeNotAnimalShelter/">Facebook page</a> for recent
					lost/found listings.</strong>
			<p>To have your lost/found listed, please <a data-email="info">email us</a>, with photos if possible.</p>
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
	<aside class="right">
		<h2>Get Involved</h2>
		<section>
			<h3>Volunteer</h3>
			TODO: Volunteer section.
		</section>
		<section class="donate">
			<h3>Donate</h3>
			<p>We are an IRS-approved 501(c)3 charitable organization. Your donations are fully tax-deductible.
			<p>Donate via PayPal<br>(one-time or monthly pledge):
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
				<script>
					function setid(os0sel) {
						if (os0sel === 'One-time donation') {
							document.getElementById('PayPalDonateInputImage').src =
									'https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif';
							document.getElementById('PayPalDonateButtonId').value = '9649881';
						} else {
							document.getElementById('PayPalDonateInputImage').src =
									'https://www.paypalobjects.com/en_US/i/btn/btn_subscribeCC_LG.gif';
							if (os0sel === '$10 Monthly Pledge') {
								document.getElementById('PayPalDonateButtonId').value = '7PHTNETHY3ZXQ';
							} else if (os0sel === '$25 Monthly Pledge') {
								document.getElementById('PayPalDonateButtonId').value = 'KZB6B2GFG5TFQ';
							} else if (os0sel === '$50 Monthly Pledge') {
								document.getElementById('PayPalDonateButtonId').value = 'XXN48CQT7UEAS';
							} else if (os0sel === '$100 Monthly Pledge') {
								document.getElementById('PayPalDonateButtonId').value = 'H8YYWFDK4W3HY';
							}
						}
					}
				</script>
				<input name="cmd" value="_s-xclick" type="hidden">
				<input name="hosted_button_id" id="PayPalDonateButtonId" value="9649881" type="hidden">
				<select name="os0" onchange="setid(this.value);">
					<option value="One-time donation" selected="">One-time donation</option>
					<option value="$10 Monthly Pledge">$10 Monthly Pledge</option>
					<option value="$25 Monthly Pledge">$25 Monthly Pledge</option>
					<option value="$50 Monthly Pledge">$50 Monthly Pledge</option>
					<option value="$100 Monthly Pledge">$100 Monthly Pledge</option>
				</select><br>
				<input src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" id="PayPalDonateInputImage" name="submit"
						alt="PayPal - The safer, easier way to pay online!" type="image">
			</form>
			<p>Donate via Network for Good<br>(one-time or monthly pledge):
				<a href="https://www.networkforgood.org/donation/ExpressDonation.aspx?ORGID2=91-1996344">
					<img src="/assets/networkforgoodlogo.gif" alt="Network for Good">
				</a>
			<p>You can help Forget Me Not when you start your Amazon shopping at
				<strong><a href="https://smile.amazon.com/ch/91-1996344">smile.amazon.com</a></strong>
		</section>
		<section class="adopted">
			<figure>
				<figcaption>
					Want to see updates on our already adopted pets?
					<br>Check our
					<a href="https://www.facebook.com/groups/135175210176154/" title="Adopted Pets">
						Adopters and Supporters Facebook Group</a>!
				</figcaption>
				<a href="https://www.facebook.com/groups/135175210176154/" title="Adopted Pets"><img alt="Adopted Pets" src="/assets/adopted.jpg"></a>
			</figure>
		</section>
	</aside>
</div>
<footer>
	<div class="f990 noprint">
		View our IRS Form 990:
		<ul>
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
							alt="2019 Member - The Washington Federation of Animal Care and Control Agencies"></a></li>
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
