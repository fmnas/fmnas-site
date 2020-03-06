<?php
declare(strict_types = 1);

function startsWith(string $haystack, string $needle): bool {
	return substr_compare($haystack, $needle, 0, strlen($needle)) === 0;
}

function endsWith(string $haystack, string $needle): bool {
	return substr_compare($haystack, $needle, -strlen($needle)) === 0;
}

function contains(string $haystack, string $needle): bool {
	return strpos($haystack, $needle) !== false;
}

/**
 * The absolute path to the site root directory (containing admin/, public/, secrets/, src/)
 */
$root = dirname(__FILE__, 1);

/**
 * The absolute path to the src directory
 */
$src = "$root/src";

/**
 * The absolute path to the templates directory
 */
$t = "$src/templates";

/**
 * The relative path to the assets directory (from the file where execution started, i.e. the current page)
 */
$assets = (function(): string {
	$cwd = getcwd();
	$host = $_SERVER["HTTP_HOST"];
	$adminSubdomain = "admin.";
	if (endsWith($cwd, "/public")) {
		return "assets";
	}
	if (contains($cwd, "/public/")) {
		return "/assets";
	}
	if (startsWith($host, $adminSubdomain)) {
		return "//" . substr($host, strlen($adminSubdomain)) . "/assets";
	}
	return "/assets"; // give up and hope
})();