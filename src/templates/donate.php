<?php
function donate(): void { ?>
	<section class="donate">
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post" class="paypal">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="hosted_button_id" value="9649881">
			<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" name="submit"
				   alt="Donate through PayPal">
			<img src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
		</form>
		<a href="https://www.networkforgood.org/donation/ExpressDonation.aspx?ORGID2=91-1996344" class="nfg"><img
				src="<?=assets()?>/networkforgoodlogo.gif" alt="Donate through Network For Good"></a>
	</section>
<?php }