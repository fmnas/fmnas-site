<?php
	require_once($_SERVER['DOCUMENT_ROOT'].'/includes/paths.php');
	require_once("$BASE/includes/db.php");

	if ($_GET["species"]) {
		$page_species = array_search(trim($_GET["species"]), array_filter(array_combine(array_keys($species),array_column($species, 'speciestext'))));
	}
	else $page_species = NULL;
	if($page_species === false) {
		header("HTTP/1.0 404 Not Found");
		die("there are no ".$_GET["species"]."s");
	}
	$pets = retrieve_adoptable_pets($page_species);
?><!DOCTYPE html>
<html lang="en-US">
<head>
	<title><?=$species[$page_species]['pagetitle']?:'Pets'?> for adoption at Forget Me Not Animal Shelter of Ferry County</title>
	<meta charset="UTF-8">

	<!-- Jquery -->
	<script src="<?=$jquery_path?>"></script>
	<script src="/<?=$document_root?>includes/email_links.js"></script>
	<script src="/<?=$document_root?>includes/listing_table.js"></script>

	<!-- Style -->
	<link rel="stylesheet" type="text/css" href="/<?=$document_root?>includes/text.css">
	<link rel="stylesheet" type="text/css" href="/<?=$document_root?>includes/header.css">
	<link rel="stylesheet" type="text/css" href="/<?=$document_root?>includes/footer.css">
	<link rel="stylesheet" type="text/css" href="/<?=$document_root?>includes/listing_table.css.php">
	<style type="text/css">
		/*Kuranda beds*/
		.kuranda p {
			color: #f00;
			background-color: #ff6;
			font-size: 28pt;
			font-family: "Arial Black", "Gadget", sans-serif;
			text-align: center;
			padding: 0.25in;
		}
		.kuranda img {
			float: none;
			display: block;
			margin-left: auto;
			margin-right: auto;
			max-width: 100%;
		}

	</style>
</head>
<body>
	<header>
		<a class="return" href="/<?=$document_root?>" title="Home">Return to the shelter home page</a>
		<a href="/<?=$document_root?>"><img class="logo" alt="Forget Me Not Animal Shelter" src="/<?=$document_root?>static/logo.gif" title="Forget Me Not Animal Shelter" width="596" height="40"></a>
		<div>
			<section class="donate">
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post" class="paypal">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="9649881">
					<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" name="submit" alt="Donate through PayPal">
					<img border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
				</form>
				<a href="https://www.networkforgood.org/donation/ExpressDonation.aspx?ORGID2=91-1996344"><img src="/<?=$document_root?>static/networkforgoodlogo.gif" alt="Donate through Network For Good"></a>
			</section>
			<section class="adopt">
				<form action="/<?=$document_root?>Application" method="POST" class="apply">
					<button>Apply Online Now</button>
				</form>
			</section>
		</div>
		<aside class="adopted">
			<a href="https://www.facebook.com/groups/135175210176154/" title="Adopted Pets">
				<figure>
					<figcaption>
						Want to see updates on our already adopted pets? Check our Facebook Adopters and Supporters Group - click here!
					</figcaption>
					<img alt="Adopted Pets" src="adopted.jpg">
				</figure>
			</a>
		</aside>
	</header>
	<?php require("$BASE/includes/listing_table.php"); ?>
	<section>
		<p><strong>Adoption Fees</strong> include Vaccinations and Spay/Neuter!
		<?php foreach ($statuses as $status):
			if($status['explanation']): ?>
		<p><strong><?=htmlspecialchars($status['statustext'])?>:</strong><br><?=nl2br(htmlspecialchars($status['explanation']))?>
		<?php endif; endforeach; ?>
	</section>
	<section class="kuranda">
		<p><a href="http://kuranda.com/donate/5567/"><img src="http://kuranda.com/images/banner1.jpg" alt="Donate a Bed"></a>
		Our dogs and cats love to sleep on Kuranda beds, but we don't have enough for everyone. If you would like to donate a bed at a special wholesale price for another dog or cat to sleep in comfort, please donate a <a href="http://kuranda.com/donate/5567/">Kuranda bed</a>.
	</section>
	<section>
		<h2>Stuff We Need!</h2>
		<p>We need <em>any</em> items in good condition, to list under our online charity <b>auctions on eBay</b> and other sites - see home page for more details! If you have items to donate, please call <a href="tel:+15097752308">775-2308</a> and leave a message or email <a data-email="donate"></a>
		<p style="font-size: large;">Items we need for the cats at the shelter: Cat litter (clay), Cat trees, Scratching posts, Catnip mice, Grooming supplies, Cat beds, 3-oz canned cat food, Small pet carriers, Cat treats
		<p>We also always need <b>LOVE LOVE LOVE</b> for the fuzzballs! Want to brush a cat or walk a dog? We need you! You can volunteer as little as 2 hours a month. Call <a href="tel:+15097752308">775-2308</a> or email <a data-email></a>
	</section>
	<footer>
		<address>
			49 W Curlew Lake Rd<br>
			Republic WA 99166-8742
		</address>
		<p class="hours">Open hours Wednesday and Saturday 1:00 - 3:00; all other days by appointment</p>
		<p>Send in an application to become a pre-approved adopter; we can schedule an appointment for you to meet all the pets that interest you at the shelter, or send the pet of your dreams out to you on one of our regular transports!
		<p class="big">For more information, call <a href="tel:+15097752308">(509)&nbsp;775-2308</a><br>
			fax <a href="tel:+12084108200">208-410-8200</a><br>
			or <a data-email>send email</a>
	</footer>
</body>
</html>
