<?php
$path = trim($_SERVER["REQUEST_URI"], "/");

require_once("../src/common.php");
require_once("$src/pet.php");

// Probable assets
if (endsWith($path, [".jpg", ".jpeg", ".txt"])) {
	require_once("asset.php");
}

// URLs starting with species (cats, dogs/1022Fido, etc)
foreach (_G_species() as $species) {
	/* @var $species Species */
	if (strtolower($path) === strtolower($species->__get("plural"))) {
		// Display adoptable pets listing
		require_once("adoptable.php");
		exit();
	}
	if (startsWith($path, $species->__get("plural") . "/")) {
		// Try displaying an individual pet
		require_once("listing.php");
	}
}

// Try asset handler again
require_once("asset.php");

// Add additional page handlers here

require_once("$src/errors/404.php");