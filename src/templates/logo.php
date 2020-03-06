<?php
require_once(__DIR__ . "/../common.php");
function logo(): void { ?>
	<a href="/"><img src="<?=assets()?>/logo.png" id="logo" alt="Forget Me Not Animal Shelter"></a>
<?php }