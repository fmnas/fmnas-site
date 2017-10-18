<!DOCTYPE html>
<?php
	require_once($_SERVER['DOCUMENT_ROOT'].'/includes/paths.php');
	require_once("$BASE/includes/db.php");
	require_once("$BASE/includes/css.php");

	if ($_GET["species"]) {
		$page_species = array_search(trim($_GET["species"]), array_filter(array_combine(array_keys($species),array_column($species, 'speciestext'))));
	}
	else $page_species = NULL;
	$pets = retrieve_adoptable_pets($page_species);
?>
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
	<style type="text/css">
		/*Kuranda beds*/
		.kuranda p {
			color: #f00;
			background-color: #ff6;
			font-size: 28pt;
			font-family: "Arial Black", Gadget, sans-serif;
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

		/*Listings table - individual listings*/
		table.listings tbody tr {
			display: inline-block;
			float: left;
			padding: 1em;
			box-sizing: border-box;
		}
		table.listings tbody tr>*:first-child {
			border-top: 1px solid #ccc;
			border-radius: 1em 1em 0 0;
			padding-top: 0.5em;
		}
		table.listings tbody tr>*:last-child {
			border-bottom:1px solid #ccc;
			border-radius: 0 0 1em 1em;
			padding-bottom: 0.5em;
		}
		table.listings tbody tr>* {
			border-left: 1px solid #ccc;
			border-right: 1px solid #ccc;
		}
		table.listings tbody td, table.listings tbody th {
			display: block;
			padding-left: 1em;
			padding-right: 1em;
			box-sizing: border-box;
		}
		table.listings tbody td.img {
			padding-left: 2em;
			padding-right: 2em;
			/* Ensures 2em margin either side of image but only 1em either side of name */
		}

		/*Listings table - individual data*/
		th.name {
			font-size: 18pt;
			display: inline;
		}
		table.listings img {
			display: block;
			width: 200px;
			height: 300px;
			margin-left: auto;
			margin-right: auto;
		}
		table.listings td, th {
			width: 100%;
			text-align: center;
			vertical-align: middle;
		}
		table.listings thead {display: none;}
		td.fee { color: red; }
		td.inquiry>a {
			 font-size: 10pt;
			 text-decoration: none;
		  }


		/*Listings table - applications pending & closed*/
		table.listings tr { order: 1; }
		table.listings tr.soon { order: 2; } /* put in middle */
		table.listings tr.closed, table.listings tr.pending {
			order: 3; /* put at end */
		}
		table.listings tr a:hover { text-decoration: none; }
		table.listings tr:not(.soon) th.name a { border-bottom: 1pt solid #066; }
		table.listings tr:not(.soon) th.name a:visited { border-bottom: 1pt solid #39f; }
		table.listings tr:not(.soon) th.name a:hover { border-bottom-width: 1.5pt; }
		tr * { background-color: #fff; }
		tr.closed *, tr.pending * {	background-color: #ddd;	}

		td.fee::before { white-space: pre-line;	}

		th.name>*::after {
			content: ' (id#' attr(id) ')';
			font-size: 11pt;
			vertical-align: 10%;
		}

		/* Status explanations */

		<?php
			$classes = array();
			foreach($statuses as $status):
				if($status['explanation']):
					$classes[] = $status['class']; ?>
		tr.<?=$status['class']?>>td.fee::before {
			content: "<?=str_replace('"',"\\\"",$status['statustext'].':\A'.str_replace(array("\r\n","\n","\r"),"\\A\\A",$status['explanation']))?>";
		}
		<?php endif; endforeach;?>

		<?=build_selector('tr.',$classes,'>td.fee>*::after')?> {
			content: "?";
			margin-left: 0.5ex;
			color: #00f;
			font-size: 9pt;
			border: 1pt solid #00f;
			padding: 0.1em;
			width: 1em;
			height: 1em;
			line-height: 1em;
			border-radius: 1em;
			vertical-align: 0.1em;
			display: inline-block;
			cursor: default;
		}
		<?=build_selector('tr.',$classes,'>td.fee>*:hover::after')?> {
			background-color: #00f;
			color: #fff;
		}
		@media print {
			<?=build_selector('tr.',$classes,'>td.fee>*::after')?> { display: none; }
		}
		<?=build_selector('tr.',$classes,'>td.fee')?> {
			overflow: visible;
			position: relative;
		}
		<?=build_selector('tr.',$classes,'>td.fee::before')?> {
			width: 100%;
			border-radius: 0.5em;
			border: 1px solid black;
			position: absolute;
			left: 50%;
			top: 1.3em;;
			margin-top: 0;
			transform: translate(-50%, 10px);
			background-color: #fff;
			color: #000;
			padding: 1em;
			opacity: 0;
			box-shadow: -2pt 2pt 5pt #000;
			text-align: justify;
			text-justify: inter-character;
			z-index: -1;
		}
		<?=build_selector('tr.',$classes,'>td.fee:hover::before')?> {
			opacity: 0.9;
			transition: all 0.18s ease-out 0.18s;
			z-index: 2;
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
	<table class="listings">
		<thead>
			<tr>
				<th>Name</th>
				<th>Sex</th>
				<th>Age</th>
				<th>Adoption fee</th>
				<th>Image</th>
				<th>Email inquiry</th>
			</tr>
		</thead>
		<tbody>
			<?php
				foreach($pets as $pet):
					$status = $statuses[$pet['status']];
					$listed = file_exists("$BASE/content/descriptions/".$pet['id'].'.html');
			?>
			<tr class="<?=$status['class'].($status['statustext']!=='Coming Soon'?'':' soon')?>">
				<th class="name"><a <?php
					if($listed):
				 ?>href="./<?=$pet['id'].$pet['name']?>"
			 <?php endif; ?> id="<?=$pet['id']?>"><?=htmlspecialchars($pet['name'])?></a></th>
				<td class="sex"><?=htmlspecialchars($sexes[$pet['sex']].' '.$pet['text1'])?></td>
				<td class="age"><time datetime="<?=$pet['dob']?>"><?php
					$dob = new DateTime($pet['dob']);
					$age = '';
					if($pet['estimate']){
						//Estimated DOB?
						$now = new DateTime();
						if($dob > new DateTime('2 years ago')) { //if <= 2 yo
							$age = ($now->diff($dob)->m) + 12*($now->diff($dob)->y);
							$age .= ' month'.($age===1?'':'s').' old';
						}
						else {
							$age = $now->diff($dob)->y;
							$age .= ' year'.($age===1?'':'s').' old';
						}
					}
					else {
						//Exact DOB?
						$age = '<abbr title="Date of birth">DOB</abbr> '.$dob->format('n/j/y');
					}
					echo $age.' '.htmlspecialchars($pet['text2']);
				?></time></td>
				<td class="fee"><span><?=htmlspecialchars($status['statustext'].' '.($status['hidefee']?'':'$'.$pet['fee']).' '.$pet['text3'])?></span></td>
				<td class="img"><a <?php
					if($listed):
				 ?>href="./<?=$pet['id'].$pet['name']?>"
			 	<?php endif; ?>>
					<img src="/<?=$document_root?>pages/get_image.php?id=<?=$pet['image']?>&amp;width=200">
				</a></td>
				<td class="inquiry"><a data-email></a></td>
			<?php endforeach; ?>
		</tbody>
	</table>
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
