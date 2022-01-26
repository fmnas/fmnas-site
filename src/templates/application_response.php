<?php
require_once __DIR__ . "/../common.php";
function application_response(): void { ?>
	<p>Thank you for your adoption application; we will be reviewing it shortly
		(applications are reviewed Sunday through Thursday), and will contact you with any additional questions.
		In the meantime, please provide any required supplemental information by responding to this message, or emailing
		us
		at <a href="mailto:<?=_G_default_email_user()?>@<?=_G_public_domain()?>">
			<?=_G_default_email_user()?>@<?=_G_public_domain()?></a> - this will help speed up your
		application processing time.

	<p><strong>If your home is not registered under your ownership with your county assessor,</strong>
		we need landlord permission for you to adopt. This can be written permission, a copy of the pet
		clause of your lease, or you can provide the landlord's name and phone number for us to contact directly
		(this includes homes owned by relatives - they always say YES, but we do need confirmation of that for our
		records).

	<p>If you do not hear back from us within 72 hours, please email us at
		<a href="mailto:<?=_G_default_email_user()?>@<?=_G_public_domain()?>">
			<?=_G_default_email_user()?>@<?=_G_public_domain()?></a>.

	<p>Thanks so much for caring about shelter pets!

	<p>
	Kim Gillen, Adoption Coordinator<br>
	<?=_G_shortname()?><br>
	<a href="tel:<?='1'.preg_replace('/[^0-9]/', '', _G_phone())?>"><?=_G_phone()?></a> (shelter)<br>
	<?=_G_fax()?> (fax)<br>
	<a href="https://<?=_G_public_domain()?>/">https://<?=_G_public_domain()?></a><br>
	like us on Facebook: <a href="https://www.facebook.com/ForgetMeNotAnimalShelter">https://www.facebook.com/ForgetMeNotAnimalShelter</a>
<?php } // @todo Move remaining fields in response to config db
