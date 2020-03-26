<?php
require_once(__DIR__ . "/logo.php");
require_once(__DIR__ . "/donate.php");
require_once(__DIR__ . "/adopt_button.php");
require_once(__DIR__ . "/adopted.php");
function pageHeader(bool $isHome = false): void { ?>
	<header>
		<nav>
			<h1><?php logo(); ?></h1>
			<?php if (!$isHome): ?>
				<a class="return" href="/" title="Home">Return to the shelter home page</a>
			<?php
			endif;
			if ($isHome):
				?>
				<ul>
					<li><a href="https://www.facebook.com/ForgetMeNotAnimalShelter/">Facebook</a>
					<li><a href="/blog">Blog</a>
				</ul>
			<?php
			endif;
			?>
		</nav>
		<div>
			<?php
			donate();
			adopt_button();
			?>
		</div>
		<?php adopted(); ?>
	</header>
<?php }
