<?php
$path = trim($_SERVER["REQUEST_URI"], "/");

require_once "../src/common.php";
require_once "$src/pet.php";

// Probable images
if (endsWith($path, ".jpeg") || $path[-4] === ".") {
	require_once "image.php";
}

// URLs starting with species (cats, dogs/1022Fido, etc)
foreach (_G_species() as $species) {
	/* @var $species Species */
	if (strtolower($path) === strtolower($species->__get("plural"))) {
		// Go to listing editor main page
		header("HTTP/1.1 303 See Other");
		header("Location: http://$_SERVER[HTTP_HOST]/listings.php");
		exit();
	}
	if (startsWith($path, $species->__get("plural") . "/")) {
		// Edit a specific listing
		require_once "listing.php";
	}
}

// Try image editor again
require_once "image.php";

require_once "$src/errors/404.php";