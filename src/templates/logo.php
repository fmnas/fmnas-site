<?php
require_once __DIR__ . "/../common.php";
function logo(): void { ?>
    <a href="/"><img src="<?=assets()?>/logo.png"
            srcset="<?=assets()?>/logo_small.png 1x, <?=assets()?>/logo_medium.png 2x, <?=assets()?>/logo_large.png 3x, <?=assets()?>/logo.png 4x"
            id="logo" alt=""><?=_G_shortname()?></a>
<?php }