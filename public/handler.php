<?php
$path = trim($_SERVER["REQUEST_URI"], "/");

require_once("../src/common.php");
require_once("$src/pet.php");

// URLs starting with species (cats, dogs/1022Fido, etc)
foreach (_G_species() as $species) {
	/* @var $species Species */
	if (strtolower($path) === strtolower($species->__get("plural"))) {
		// Display adoptable pets listings
		require_once("adoptable.php");
		exit();
	}
	if (startsWith($path, $species->__get("plural"))) {
		// Display an individual pet
		require_once("listing.php");
		exit();
	}
}

// Add additional page handlers here

require_once("$src/errors/404.php");