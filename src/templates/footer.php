<?php
require_once __DIR__ . "/../common.php";
function footer(): void { ?>
	<footer>
		<p><a href="/application">Send in an application</a> to become a pre-approved adopter; we can schedule an
			appointment for you to meet all the pets that interest you at the shelter, or send the pet of your dreams
			out to you on one of our regular transports!
		<p>For more information, call&nbsp;<a href="tel:<?=_G_phone_intl()?>"><?=_G_phone()?></a>,
			fax&nbsp;<?=_G_fax()?>,
			or email <a data-email="info"></a>
		<p>&copy; 2004&ndash;<?=date('Y') /* TODO: Make the copyright year dependent on last change */?> <?=_G_longname()?>, <?=str_replace("\n", ", ", _G_address())?></p>
	</footer>
<?php }
