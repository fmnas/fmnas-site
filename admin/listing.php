<?php
require_once "auth.php";
require_once "$src/pet.php";
require_once "$src/db.php";
require_once "$src/assets.php";

/* @var $path string */
$db ??= new Database();
$pet = null;
if (@isset($path)) {
	$pet = $db->getPetByPath($path);
} elseif (@isset($_GET["id"])) {
	$pet = $db->getPetById($_GET["id"]);
}
if ($pet === null || $pet->status->deleted) {
	return; // this is not a valid listing
}

// TODO: listing editor


exit(0); // Exit from handler.php if the listing was found