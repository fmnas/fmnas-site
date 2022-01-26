<?php
require_once "pet.php";
require_once "db.php";
require_once "common.php";
require_once "css.php";

/**
 * Generate a static configuration file, generated.php, using data from the database.
 * This function will be called only if generated.php does not already exist or the values are modified from the admin
 * interface.
 */
function generate() {
	global $db;
	$db ??= new Database();

	$values = [];

	foreach ($db->query("SELECT * FROM config") as $item) {
		if (!preg_match("/^[a-z0-9][a-z0-9_]*[a-z0-9]$/i", $item["config_key"])) {
			log_err("Got invalid config key {$item['config_key']} from database! Aborting generator!");
			require_once src() . "/errors/500.php";
			exit(500);
		}
		$values[$item["config_key"]] = $item["config_value"];
	}

	$values["species"] = [];
	foreach ($db->getAllSpecies() as $s) {
		/* @var $s Species */
		$s->species_count = null;
		$values["species"][$s->id] = $s;
	}

	$values["sexes"] = [];
	foreach ($db->query("SELECT * FROM sexes") as $item) {
		$sex = new Sex();
		$sex->name = $item["name"];
		$sex->key = $item["id"];
		$values["sexes"][$sex->key] = $sex;
	}

	$values["statuses"] = [];
	foreach ($db->query("SELECT * FROM statuses") as $item) {
		$status = new Status();
		$status->key = $item["id"];
		$status->description = $item["description"];
		$status->displayStatus = $item["display"];
		$status->listed = $item["listed"];
		$status->name = htmlspecialchars($item["name"]);
		$values["statuses"][$status->key] = $status;
	}

	ob_start();
	?>

	// This is a static configuration file generated from the database.
	// Instead of changing values in this file, you should simply delete it and allow it to be regenerated.

	require_once __DIR__."/pet.php";$_G=unserialize(base64_decode("<?=base64_encode(serialize($values));?>"));<?php
	foreach ($values as $key => $value):
		?> function _G_<?=$key?>(){global $_G;return $_G["<?=$key?>"];}<?php
	endforeach;
	$output = "<?php" . ob_get_clean();
	file_put_contents(src() . "/generated.php", $output);

	// Generate some CSS for the adoptable page
	ob_start();
	$displayedStatusSelectors = [];
	$hoverStatusSelectors = [];
	foreach ($values["statuses"] as $status) {
		/* @var $status Status */
		if (isset($status->displayStatus) && $status->displayStatus) {
			$sel = "tr.st_{$status->key}";
			$displayedStatusSelectors[] = $sel;
			echo $sel . '>td.fee>*::before{content:"';
			echo cssspecialchars($status->name);
			echo '";}';
			// TODO [#76]: Render status text in fee cell on server side for a11y
			if (isset($status->description) && strlen(trim($status->description)) > 0) {
				$hoverStatusSelectors[] = $sel;

				// The content to display when hovering over the status
				echo $sel . '>td.fee::before{content:"';
				echo cssspecialchars($status->name . ":\n" . $status->description);
				echo '";}';
			}
		}
	}

	if (count($displayedStatusSelectors)) {
		// Display pending animals with a grey background
		echo buildSelector($displayedStatusSelectors, " *");
		echo "{background-color:var(--pending-color);}";

		// Show status instead of fee
		echo buildSelector($displayedStatusSelectors, ">td.fee>span");
		echo "{display:none;}";

		// Move them to the end
		echo buildSelector($displayedStatusSelectors);
		echo "{order:3 !important;}";

		// Hide email link
		echo buildSelector($displayedStatusSelectors, " .inquiry>a");
		echo "{visibility:hidden;}";
	}

	if (count($hoverStatusSelectors)) {
		// Display the ? to hover over to see the status
		echo buildSelector($hoverStatusSelectors, ">td.fee>*::after") . <<<CSS
            {
                content: "?";
                margin-left: 0.38ex;
                color: var(--info-color);
                font-size: 9pt;
                border: 1pt solid var(--info-color);
                padding: 0.1em;
                width: 1em;
                height: 1em;
                line-height: 1em;
                border-radius: 1em;
                vertical-align: 0.1em;
                display: inline-block;
                cursor: default;
            } 
            CSS;

		// Make the ? a different color when hovering
		echo buildSelector($hoverStatusSelectors, ">td.fee>*:hover::after");
		echo "{background-color:var(--info-color);color:var(--background-color);} ";

		// Hide the ? when printing
		echo "@media print {";
		echo buildSelector($hoverStatusSelectors, ">td.fee>*::after");
		echo "{display: none;} } ";

		// Make the popup able to overflow outside the box when hovering
		echo buildSelector($hoverStatusSelectors, ">td.fee>*::after");
		echo "{overflow:visible;position:relative;} ";

		// Style the popup
		echo buildSelector($hoverStatusSelectors, ">td.fee::before") . <<<CSS
            {
                width: 100%;
                border-radius: 0.5em;
                border: 1px solid black;
                position: absolute;
                left: 50%;
                top: 1.3em;
                margin-top: 0;
                transform: translate(-50%, 10px);
                background-color: var(--background-color);
                color: var(--text-color);
                padding: 1em;
                opacity: 0;
                box-shadow: -2pt 2pt 5pt #000;
                text-align: justify;
                text-justify: inter-character;
                z-index: -1;
                transition: opacity 0.18s ease-in 0.18s, z-index 0s 0.36s;
            }
            CSS;
		echo buildSelector($hoverStatusSelectors, ">td.fee:hover::before");
		echo "{opacity:0.9;transition:opacity 0.18s ease-out 0.18s;z-index:2;} ";
		echo buildSelector($hoverStatusSelectors, ">td.fee");
		echo "{overflow:visible;position:relative;}";
	}
	$output = ob_get_clean();
	// TODO [#77]: Minify adoptable.generated.css
	file_put_contents(root() . "/public/adoptable.generated.css", $output);
}
