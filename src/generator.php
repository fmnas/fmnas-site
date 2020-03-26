<?php
require_once("pet.php");
require_once("db.php");

/**
 * Generate a static configuration file, generated.php, using data from the database.
 * This function will be called only if generated.php does not already exist or the values are modified from the admin interface.
 */
function generate() {
	$db = new Database();

	$values = array();

	// TODO: Get these from the database
	$values["shortname"]      = "Forget Me Not Animal Shelter";
	$values["longname"]       = "Forget Me Not Animal Shelter of Ferry County";
	$values["transport_date"] = "2020-03-14";
	$values["address"]        = "49 W Curlew Lake Rd\nRepublic WA 99166-8742";
	$values["phone_intl"]     = "+15097752308";
	$values["phone"]          = "(509)&nbsp;775-2308";
	$values["fax"]            = "208-410-8200";
	// TODO: validate values

	$values["species"] = [];
	$species           = $db->getAllSpecies();
	foreach ($species as $s) {
		/* @var $s Species */
		$s->__set("species_count", null);
		$values["species"][$s->__get("id")] = $s;
	}

	$male            = new Sex();
	$male->name      = "male";
	$male->key       = 1;
	$female          = new Sex();
	$female->name    = "female";
	$female->key     = 2;
	$values["sexes"] = [1 => $male, 2 => $female];

	$adoptable                = new Status();
	$adopted                  = new Status();
	$pending                  = new Status();
	$closed                   = new Status();
	$deleted                  = new Status();
	$adoptable->key           = 1;
	$adopted->key             = 2;
	$pending->key             = 3;
	$closed->key              = 4;
	$deleted->key             = 5;
	$adoptable->name          = "Adoptable";
	$adopted->name            = "Adopted";
	$pending->name            = "Adoption Pending";
	$closed->name             = "Applications Closed";
	$deleted->name            = "DELETE";
	$adoptable->deleted       = false;
	$adopted->deleted         = false;
	$pending->deleted         = false;
	$closed->deleted          = false;
	$deleted->deleted         = false;
	$adoptable->displayStatus = false;
	$pending->displayStatus   = true;
	$closed->displayStatus    = true;
	$adoptable->listed        = true;
	$adopted->listed          = false;
	$pending->listed          = true;
	$closed->listed           = true;
	$deleted->listed          = false;
	$values["statuses"]       = [1 => $adoptable, 2 => $adopted, 3 => $pending, 4 => $closed, 5 => $deleted];

	ob_start();
	?>

	// This is a static configuration file generated from the database.
	// Instead of changing values in this file, you should simply delete it and allow it to be regenerated.

	function _G(){return unserialize("<?=addslashes(serialize($values));?>");} $_G=_G(); <?php
	foreach ($values as $key => $value):
		// TODO: validate key!
		?> function _G_<?=$key?>(){global $_G; return $_G["<?=$key?>"];}<?php
	endforeach;
	$output = "<?php" . ob_get_clean();
	file_put_contents(__DIR__ . "/generated.php", $output);
}