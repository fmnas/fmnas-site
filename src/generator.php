<?php
require_once "pet.php";
require_once "db.php";
require_once "common.php";
require_once "css.php";
require_once "form.php";

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

	$values["forms"] = [];
	foreach ($db->query("SELECT * FROM forms") as $item) {
		$form = new Form();
		$form->id = $item["id"];
		$form->title = $item["title"];
		$form->fillout_id = $item["fillout_id"];
		$values["forms"][$form->id] = $form;
	}

	ob_start();
	?>

	// This is a static configuration file generated from the database.
	// Instead of changing values in this file, you should simply delete it and allow it to be regenerated.

	require_once __DIR__."/pet.php";
	require_once __DIR__."/form.php";
	$_G=unserialize(base64_decode("<?=base64_encode(serialize($values));?>"));<?php
	foreach ($values as $key => $value):
		?> function _G_<?=$key?>(){global $_G;return $_G["<?=$key?>"];}<?php
	endforeach;
	$output = "<?php" . ob_get_clean();
	file_put_contents(src() . "/generated.php", $output);
}
