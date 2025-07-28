<?php
require_once __DIR__ . "/analytics.php";
function pageHeader(): void {
	analytics();
	?>
	<header>
		<nav>
			<div class="main">
				<a href="/" class="logo"><img src="<?=assets()?>/logo.png"
							srcset="<?=assets()?>/logo_small.png 1x, <?=assets()?>/logo_medium.png 2x, <?=assets()?>/logo_large.png 3x, <?=assets()?>/logo.png 4x"
							alt=""></a>
				<h1><a href="/"><?=_G_shortname()?></a></h1>
				<div class="contact">
					<address><span><?=str_replace("\n", "</span><span>", mb_substr(_G_address(), 0, -5))?></span></address>
					<a class="tel" href="tel:<?=_G_phone_intl()?>"><?=_G_phone()?></a>
				</div>
				<ul class="social">
					<li><a href="/">Home</a>
					<li><a href="https://www.facebook.com/ForgetMeNotAnimalShelter/">Facebook</a>
					<li><a href="/blog">Blog</a>
				</ul>
				<a class="printonly url" href="/">https://<?=_G_public_domain()?></a>
			</div>
			<form class="adopt" action="/application" method="POST">
				<h2><a href="/application">Adopt a Pet</a></h2>
				<button type="submit">Apply Online Now</button>
			</form>
			<section class="donate">
				<form action="https://www.paypal.com/donate" method="post" class="paypal">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="EYQVKDS74KYZ2">
					<input type="image" src="/assets/btn_donateCC_LG.gif" name="submit"
							alt="Donate through PayPal">
				</form>
				<a href="https://www.networkforgood.org/donation/ExpressDonation.aspx?ORGID2=91-1996344" class="nfg darken"><img
							src="<?=assets()?>/networkforgoodlogo.gif" alt="Donate through Network For Good"></a>
			</section>
		</nav>
	</header>
<?php }
