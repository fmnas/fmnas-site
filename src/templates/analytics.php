<?php
require_once __DIR__ . "/../analytics.php";
require_once __DIR__ . "/../../secrets/config.php";

function analytics(): void {
	?>
	<script async src="https://www.googletagmanager.com/gtag/js?id=<?=Config::$ga_id?>"></script>
	<script>
		window.dataLayer = window.dataLayer || [];

		function gtag() {
			dataLayer.push(arguments);
		}

		gtag('js', new Date());
		gtag('config', '<?=Config::$ga_id?>');
	</script>
	<?php
}

?>
