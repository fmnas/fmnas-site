<?php
/*
 * Copyright 2022 Google LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once "../../src/common.php";
require_once "$t/header.php";
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
	<title>Adoption Application - <?=_G_longname()?></title>
	<meta charset="UTF-8">
	<meta name="robots" content="nofollow">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="canonical" href="https://<?=_G_public_domain()?>/application/faq.php">
	<script src="/email.js.php"></script>
	<?php
	style();
	style("minheader", true);
	?>
	<style>
		.bold {
			font-weight: bold;
		}
		body, #minimal_header {
			text-align: center;
		}
		body > article {
			display: inline-block;
			text-align: justify;
		}
		p, address {
			max-width: 9in;
			margin: 0 5vw 1em 5vw;
		}
		address {
			font-style: normal;
		}
		h2 {
			margin: 2rem 0;
		}
		p > strong:first-child {
			color: inherit;
		}
	</style>
</head>
<body>
<?php
//ob_start();
//pageHeader();
//echo str_replace("<header>", "<header data-remove='true'>", ob_get_clean());
?>
<article>
	<header id="minimal_header">
		<a href="/">
			<h1><?=_G_shortname()?></h1>
			<address><p><?=mb_strcut(str_replace("\n", "<p>", _G_address()), 0, -5)?></address>
			<span class="tel"><?=_G_phone()?></span>
		</a>
	</header>
	<h2>ADOPTION INFORMATION/FAQ</h2>
	<section class="bold">
		<p>Our pets arrive in your home already spayed or neutered, and with all age appropriate vaccinations.
		<p>We ask a varying minimum adoption donation as noted in the listing; this depends on adoptability, age, medical
			expenses, and length of time at the shelter.
		<p>Like all desperate nonprofit animal rescue organizations, we gratefully accept adoption donations above the
			minimum from those who can afford to give more, but we have found many, many wonderful homes for our pets with
			families that could not afford more than our minimums. We celebrate those homes as ecstatically as we celebrate
			homes that can afford more, and we prefer that you not tell us how much you plan to donate until after your
			adoption has been approved.</strong></p>
	</section>
	<p>The first step toward adoption is to fill out the application.</p>
	<p><strong>Filling out the application in no way commits you to adopting</strong> - it simply enables us to get you
		preapproved, in
		case you decide you want to adopt. If you are then approved and offered a pet placement, you may always refuse the
		placement. If you accept a placement, we always give you a 30-day money-back trial period; if during that time you
		decide that your new pet simply won't work out, for any
		reason, we will accept the pet back with a full refund of your adoption payment. You could either return the pet
		directly to us, or have it picked up on our next transport to your area! I must say, we are very proud of our
		successful placement record - we really work hard to make sure you and your pet will be a great match.</p>
	<p>To send the requested photos or documents, please attach them to your application, email them to <a data-email></a>,
		fax them (but not photos, please) to <?=_G_fax()?>, or "snail mail" them to:</p>
	<address>
		Adoption Coordinator<br>
		<?=_G_shortname()?><br>
		<?=nl2br(_G_address())?>
	</address>
	<p><strong>If you rent,</strong> we must obtain landlord permission prior to approving your application. If you are
		unable to get written approval as described on the application, please at least let us know your landlord's name and
		contact information so we can speak with them directly.
	<p>
		The best <strong>photos to send for dog applications</strong> would be photos of your fence, yard, home, any
		dog-specific areas like kennels or dog runs, and any photos of your current or former pets you would like to share.
	<p>
		For <strong>cat adoptions</strong>, just send us some photos of your home that you think show how much a kitty would
		love it there, and photos of your current or former pets. We know cats just laugh at your fence, and don't care if
		you live in a huge home or a cozy cottage!
	<p>
		If we have an upcoming <strong>transport</strong> scheduled to your area, we will make every effort to process and
		approve your application in time for the transport. Our regular transports to <strong>Monroe or Spokane</strong> do
		not cost anything extra, but an optional donation to help us cover our transport expenses (which can be as high as
		$300 per trip) is always appreciated from those who can afford to help. We do adopt to Canadians, but you must pick
		up your pet on this side of the border; contact us for further information.
	<p>
		If you have any additional comments or information you wish to share beyond that which is covered in the
		application, please use the comments section at the end. We will contact you within 24 hours of receiving your
		application; if you do not hear from a real person (rather than an auto-generated 'thanks for your application'
		message) within 24 hours, please either email us or call us - it's possible your application was eaten in cyberspace
		(it's rare, but it can happen).
	<p>
		Thanks so much for looking at shelter pets, they really make the best buddies!</p>
</article>
