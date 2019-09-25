<!DOCTYPE html>
<html lang="en-US">
<head>
	<title>Cats for adoption at Forget Me Not Animal Shelter of Ferry County</title>
	<meta charset="UTF-8">
	<meta data-nominalURL="http://forgetmenotshelter.org/Cats/"> <!-- for offline use -->
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<!-- Jquery -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
	<script type="text/javascript">
		//Relative paths for offline testing
		if ($(location).attr('href').startsWith('file')) $('head').append('<base href="'+$('meta[data-nominalURL]').attr('data-nominalURL')+'">');

		//Email links
		$(function() {
			$("a[data-email]").each(function() { //for every email link (with data-email attribute)
				user = 'info'; //default value
				domain = '@forgetmenotshelter.org';
				data = $(this).attr('data-email');
				subject = '';
				if(!!$.trim(data)) user=data; //if data-email attribute non-empty, use as user part
				else if ($(this).parent().is('td.inquiry')) { //empty in inquiry link on listing
					pet = $(this).closest('tr').find('th.name>*').first(); //find name on listing
					user = 'adopt+'+pet.attr('id'); //add id to adopt+ for user
					subject = pet.text();
					$(this).text('Email to adopt '+subject+'!'); //set link text
				}
				if(user.charAt(0)=='+') user='adopt'+user; //for adoption links
				if(!$.trim($(this).text())) $(this).html(user+domain); //make text email address if still blank
				$(this).attr('href','mailto:'+user+domain+'?subject='+subject); //set href
			});
		});

		//Sort table
		$(function(){ //set data-originalorder on each tr
			$('table.listings tbody tr').attr('data-originalorder',function(index){return index;});
		});
		$(function(){sortOn('original')}); //Set how to sort: dob, id, name, fee, original - add a 'desc' to make previous descending

		var sortArgs;
		function sortOn() {
			table = $('table.listings tbody').first();
			sortArgs = arguments;
			rows = table.find('tr').toArray().sort(function(a, b){
				for(i=0; i<sortArgs.length; i++){
					comp = getcomparator(sortArgs[i])(a, b);
					if(sortArgs[i+1] == 'desc') {
						comp *= -1;
						i++;
					}
					if(comp) return comp;
				}
				return 0;
			});
			for(i=0;i<rows.length;i++) { table.append(rows[i]); }
		}
		function getcomparator(property) {
			switch (property) {
				case 'dob':
					return function(a, b){
						da = new Date($(a).find('time').first().attr('datetime'));
						db = new Date($(b).find('time').first().attr('datetime'));
						return da - db;
					};
				case 'id':
					return function(a, b){
						da = $(a).find('th>a[id]').first().attr('id');
						db = $(b).find('th>a[id]').first().attr('id');
						return da.localeCompare(db);
					};
				case 'name':
					return function(a, b){
						da = $(a).find('th>a[id]').first().text();
						db = $(b).find('th>a[id]').first().text();
						return da.localeCompare(db);
					};
				case 'fee':
					return function(a, b){
						da = parseInt($(a).find('td.fee>span').first().text().match(/\d+/)[0],10)||0;
						db = parseInt($(b).find('td.fee>span').first().text().match(/\d+/)[0],10)||0;
						return da - db;
					};
				case 'original':
					return function(a, b){
						da = parseInt($(a).attr('data-originalorder'));
						db = parseInt($(b).attr('data-originalorder'));
						return da - db;
					};
			}
			return function(a,b){return 0;}
		}

		function disablePendingShove() { //Stop pending/closed listings from going to the end
			$('table.listings tbody tr').attr('style','order: 0 !important;');
		}
		function enablePendingShove() { //Cause pending/closed listings to go to the end
			$('table.listings tbody tr').attr('style','');
		}

		$(function(){
			enablePendingShove();
		})
	</script>

	<!-- Style -->
	<style type="text/css">

		/*Text*/
		body {
			color: #000;
			font-family: sans-serif;
			font-size: 12pt;
			text-align: justify;
		}
		a { text-decoration: none; }
		a:link { color: #066; }
		a:visited { color: #39f; }
		a:hover { text-decoration: underline; }
		p>strong:first-child{
			font-weight: bold;
			color: red;
			display: inline;
		}
		section, footer {
			display: block;
			width: 100%;
			padding-left: 6%;
			padding-right: 6%;
			box-sizing: border-box;
		}
		section>h2 {
			text-align: center;
			color: #069;
			font-size: 14pt;
			margin-bottom: 0;
		}
		p {
			margin-top: 0;
			margin-bottom: 1em;
		}

		/*Header*/
		header {
			display: flex;
			justify-content: space-between;
			flex-wrap: wrap;
			width: 100%;
			padding: 0 3% 0 3%;
			box-sizing: border-box;
			align-items: flex-start;
		}
		@media print {
			header {
				display: block;
			}
		}
		header * {
			display: inline-block;
			vertical-align: middle;
		}
		header>* {
			flex-grow: 1;
		}
		img.logo {
			max-width: 100%;
			height: auto;
		}
		a.return {
			width: 100%;
			text-align: right;
			font-size: 10pt;
			margin-bottom: 0.25in;
		}
		aside.adopted, aside.adopted figure {
			text-align: right;
			margin: 0;
		}
		aside.adopted>* {
			margin-left: auto;
		}
		aside.adopted figcaption {
			max-width: 2.5in;
			font-size: 11pt;
		}
		aside.adopted img {
			width: 1in;
		}
		header section, header>div, header>aside {
			display: flex;
			flex-wrap: wrap;
			justify-content: space-evenly;
		}
		@media print {
			header section, header>div, header>aside {
				display: block;
			}
		}
		header * { margin-bottom: 5pt; }
		header>div {
			flex-grow: 5;
			flex-direction: column;
		}
		.apply button {
			font-size: 18pt;
			padding: 5pt;
		}

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

		/*Footer*/
		footer {
			text-align: justify;
			text-align-last: center;
		}
		address { font-style: normal; }
		.hours {
			font-size: 16pt;
			text-transform: uppercase;
			color: #f00;
		}
		.big, footer>address { font-size: 28pt; }

		/*Listings table*/
		table.listings {
			 width: 100%;
			 display: block;
			 /*background-color: #ddd;*/
		}
		table.listings tbody {
			display: flex;
			justify-content: space-around;
			flex-wrap: wrap;
		}
		@media print {
			table.listings tbody {
				display: block;
			}
		}

		/*Listings table - individual listings*/
		table.listings tbody tr {
			float: left;
			max-width: 50%;
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
			object-fit: contain;
			margin-left: auto;
			margin-right: auto;
			max-width: calc(50vw - 6em);
			max-height: calc((50vw - 6em) * 1.5);
		}
		table.listings td, th {
			width: 100%;
			text-align: center;
			vertical-align: middle;
		}
		table.listings thead {display: none;}
		td.fee { color: red; }
		td.inquiry {
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}
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
		tr.soon>td.fee>*::before {
			content: "Coming Soon";
		}
		tr.closed>td.fee>*::before {
			content: "Applications Closed";
		}
		tr.pending>td.fee>*::before {
			content: "Adoption Pending";
		}
		tr.closed>td.fee>*::after, tr.pending>td.fee>*::after {
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
		tr.closed>td.fee>*:hover::after, tr.pending>td.fee>*:hover::after {
			background-color: #00f;
			color: #fff;
		}
		tr.closed>td.fee>span, tr.pending>td.fee>span, tr.soon>td.fee>span {
			display: none;
		}
		th.name>*:not([id=''])::after {
			content: ' (id#' attr(id) ')';
			font-size: 11pt;
			vertical-align: 10%;
		}
		@media print {
			tr.closed>td.fee>*::after, tr.pending>td.fee>*::after { display: none; }
		}
		tr.closed>td.fee, tr.pending>td.fee {
			overflow: visible;
			position: relative;
		}
		tr.closed>td.fee::before, tr.pending>td.fee::before {
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
		tr.closed>td.fee:hover::before, tr.pending>td.fee:hover::before {
			opacity: 0.9;
			transition: all 0.18s ease-out 0.18s;
			z-index: 2;
		}
		td.fee::before { white-space: pre-line;	}
		tr.closed>td.fee::before {
			content: "Applications Closed:\AWe have received a fairly large number of applications in a fairly short period of time, and need a chance to review them to see if any will be a great match to the particular pet. If the right match is not found in the applications already received, we will REOPEN applications. \A\AYou may still submit an application for one of these pets, and we will review it right away if the right match is not found first."
		}
		tr.pending>td.fee::before {
			content: "Adoption Pending:\AWe either have so many applications we are confident of finding the pet's new home from among them, OR the pet has been offered to an applicant who has accepted placement, and we will be delivering the pet on the next Seattle or Spokane trip.\A\AYou can submit an application for one of these pets if you'd like to be a \"backup home\" should anything not work out with the prior applicants, but it's a longshot."
		}

		/*Print links*/
		@media print {
			header aside, header a.return { display: none; }
			a[href]::after { content: ' <' attr('href') '>'; }
			form[action]::after { content: ' <' attr('action') '>'; }
		}
		  
		@media (max-width: 600px) {
			table.listings tbody td.img {
				padding-left: 1em;
				padding-right: 1em;
			}
			table.listings tbody tr {
				width: 50%;
			}
			body {
				font-size: 10pt;
			}
			th.name {
				font-size: 14pt;
			}
			th.name>*:not([id=''])::after {
				font-size: 8pt;
			}
			tr.closed>td.fee>*::after, tr.pending>td.fee>*::after {
				display: none;
			}
		}

		@media (max-width: 400px) {
			th.name>*:not([id=''])::after {
				display: none;
			}
		}
	</style>
</head>
<body>
	<header>
		<a class="return" href="/" title="Home">Return to the shelter home page</a>
		<a href="/"><img class="logo" alt="Forget Me Not Animal Shelter" src="/_graphics/logo2.gif" title="Forget Me Not Animal Shelter" width="596" height="40"></a>
		<div>
			<section class="donate">
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post" class="paypal">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="9649881">
					<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" name="submit" alt="Donate through PayPal">
					<img border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
				</form>
				<a href="https://www.networkforgood.org/donation/ExpressDonation.aspx?ORGID2=91-1996344"><img src="/Cats/networkforgoodlogo.gif" alt="Donate through Network For Good"></a>
			</section>
			<section class="adopt">
				<form action="/Application" method="POST" class="apply">
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
				if(!($catscsv = fopen('cats.csv','r'))) die();
				while(($data = fgetcsv($catscsv)) !== FALSE):
				$listed=file_exists($data[1].$data[0].'/'.$data[7]);
			?>
			<tr class="<?=$data[8].' '.($listed?'':'soon')?>">
				<th class="name"><a <?php
					if($listed):
				 ?>href="<?=htmlspecialchars($data[1].$data[0])?>"
			 	<?php endif; ?> id="<?=$data[1]?>"><?=$data[0]?></a></th>
				<td class="sex"><?=$data[2].' '.$data[5]?></td>

				<td class="age">
					<?php if(trim($data[4]) != ""): ?>
					<time datetime="<?=$data[4]?>"><?php
					$dob = new DateTime($data[4]);
					$age = '';
					$now = new DateTime();
					if($dob < new DateTime('1 year ago')) { // if >= 1 yo
						$age = $now->diff($dob)->y;
						$age .= ' year'.($age===1?'':'s').' old';
					}
					else if($dob < new DateTime('4 months ago')) {
						$age = $now->diff($dob)->m;
						$age .= ' month'.($age===1?'':'s').' old';
					}
					else {
						$age = 'DOB '.$dob->format('n/j/y');
					}
					echo $age;
				?></time>
				<?php endif; ?></td>
				<td class="fee"><div></div><span><?=$data[6]?></span></td>
				<td class="img"><a <?php
					if($listed):
				 ?>href="<?=htmlspecialchars($data[1].$data[0])?>"
			 	<?php endif; ?>>
					<img src="<?php if($listed): ?><?=
					htmlspecialchars($data[1].$data[0])?>/<?php endif; ?><?=
					htmlspecialchars($data[7])?>" alt="<?=$data[0]?>">
				</a></td>
				<td class="inquiry"><a data-email></a></td>
			<?php endwhile; ?>
		</tbody>
	</table>
	<section>
		<p><strong>Adoption Fees</strong> include Vaccinations and Spay/Neuter!
		<p><strong>Adoption Pending:</strong><br>We either have so many applications we are confident of finding the pet's new home from among them, OR the pet has been offered to an applicant who has accepted placement, and we will be delivering the pet on the next Seattle or Spokane trip.<p>You can submit an application for one of these pets if you'd like to be a &quot;backup home&quot; should anything not work out with the prior applicants, but it's a longshot.
		<p><strong>Applications Closed:</strong><br>We have received a fairly large number of applications in a fairly short period of time, and need a chance to review them to see if any will be a great match to the particular pet. If the right match is not found in the applications already received, we will REOPEN applications.<p>You may still submit an application for one of these pets, and we will review it right away if the right match is not found first.
	</section>
	<hr>
	<section>
		<p>We always need <b>LOVE LOVE LOVE</b> for the fuzzballs! Want to brush a cat or walk a dog? We need you! You can volunteer as little as 3 hours a month. Call <a href="tel:+15097752308">775-2308</a> or email <a data-email></a>
	</section>
	<footer>
		<address>
			49 W Curlew Lake Rd<br>
			Republic WA 99166-8742
		</address>
		<p class="hours">DUE TO COVID-19, ALL SHELTER VISITS FOR ANY REASON MUST BE DONE BY APPOINTMENT ONLY - <span style="text-transform: lowercase;">call or email to make an appointment for a day/time that is convenient for you</span>
		<p>Send in an application to become a pre-approved adopter; we can schedule an appointment for you to meet all the pets that interest you at the shelter, or send the pet of your dreams out to you on one of our regular transports!
		<p class="big">For more information, call <a href="tel:+15097752308">(509)&nbsp;775-2308</a><br>
			fax <a href="tel:+12084108200">208-410-8200</a><br>
			or <a data-email>send email</a>
	</footer>
</body>
</html>
