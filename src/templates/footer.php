<?php
require_once __DIR__ . "/../common.php";
function footer(): void { ?>
<footer>
	<address><?=nl2br(_G_address(), false)?></address>
	<p class="hours">Open hours Wednesday and Saturday 1:00&ndash;3:00; all other days by appointment</p>
	<p><a href="/application">Send in an application</a> to become a pre-approved adopter; we can schedule an appointment for you to meet all the pets that interest you at the shelter, or send the pet of your dreams out to you on one of our regular transports!
	<p class="big">For more information, call&nbsp;<a href="tel:<?=_G_phone_intl()?>"><?=_G_phone()?></a><br>
		fax&nbsp;<?=_G_fax()?><br>
		or email <a data-email></a>
</footer>
<?php }