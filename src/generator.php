<?php
require_once("pet.php");
require_once("db.php");

/**
 * Generate a static configuration file, generated.php, using data from the database.
 * This function will be called only if generated.php does not already exist or the values are modified from the admin interface.
 */
function generate() {
	$values = array();

	// TODO: Get these from the database
	$values["shortname"]      = "Forget Me Not Animal Shelter";
	$values["longname"]       = "Forget Me Not Animal Shelter of Ferry County";
	$values["transport_date"] = "2020-03-14";

	$dog = new Species();
	$dog->setAll([
		"id" => "2",
		"name" => "dog",
		"plural" => "dogs",
		"young" => "puppy",
		"young_plural" => "puppies",
		"old" => "senior dog",
		"old_plural" => "senior dogs",
		"age_unit_cutoff" => 12,
		"young_cutoff" => 6,
		"old_cutoff" => 96
	]);
	$cat = new Species();
	$cat->setAll([
		"id" => "1",
		"name" => "cat",
		"plural" => "cats",
		"young" => "kitten",
		"young_plural" => "kittens",
		"old" => "senior cat",
		"old_plural" => "senior cats",
		"age_unit_cutoff" => 12,
		"young_cutoff" => 6,
		"old_cutoff" => 96
	]);
	$values["species"] = ["1" => $cat, "2" => $dog];

	$male = new Sex();
	$male->name = "male";
	$male->key = "1";
	$female = new Sex();
	$female->name = "female";
	$female->key = "2";
	$values["sexes"] = ["1" => $male, "2" => $female];

	ob_start();
	?>

// This is a static configuration file generated from the database.
// Instead of changing values in this file, you should simply delete it and allow it to be regenerated.

function _G(){return deserialize("<?php
	echo serialize($values);
	?>");}<?php
	foreach ($values as $key => $value):
		?> function _G_<?=$key?>(){return _G()["<?=$key?>"];}<?php
	endforeach;
	$output = "<?php" . ob_get_clean();
	file_put_contents(__DIR__ . "/generated.php", $output);
}