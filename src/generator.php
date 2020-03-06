<?php
/**
 * Generate a static configuration file, generated.php, using data from the database.
 * This function will be called only if generated.php does not already exist or the values are modified from the admin interface.
 */
function generate() {
	$values = array();

	// TODO: Get these from the database
	$values["shortname"] = "Forget Me Not Animal Shelter";
	$values["longname"]  = "Forget Me Not Animal Shelter of Ferry County";

	ob_start();
	?>

	// This is a static configuration file generated from the database.
	// Instead of changing values in this file, you should simply delete it and allow it to be regenerated.

	<?php foreach ($values as $key => $value): ?> function _G_<?=$key?>(){return "<?=addslashes($value)?>";}<?php
	endforeach;
	$output = "<?php" . ob_get_clean();
	file_put_contents(__DIR__ . "/generated.php", $output);
}