<?php
$path = trim(strtok($_SERVER["REQUEST_URI"], "?"), "/");

require_once "../src/common.php";
require_once "$src/pet.php";
require_once "$src/form.php";

// Probable assets
if ($path[-4] === "." || endsWith($path, ".jpeg")) {
	require_once "asset.php";
}

// URLs starting with species (cats, dogs/1022Fido, etc)
foreach (_G_species() as $species) {
	/* @var $species Species */
	if (strtolower($path) === strtolower($species->plural())) {
		// Display adoptable pets listing
		require_once "adoptable.php";
		exit();
	}
	if (startsWith($path, $species->plural() . "/")) {
		// Try displaying an individual pet
		$expectListing = true;
		require_once "listing.php";
	}
}

// URLs for known forms
foreach (_G_forms() as $form) {
	/* @var $form Form */
	if (strtolower($path) === strtolower($form->id)) {
		require_once "$public/form.php";
		exit();
	}
}

// Try asset handler again
require_once "asset.php";

require_once "$src/errors/404.php";
