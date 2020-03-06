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
 * @return string The absolute path to the site root directory (containing admin/, public/, secrets/, src/)
 */
function root(): string {
	return dirname(__DIR__, 1);
}

/**
 * @return string The absolute path to the src directory
 */
function src(): string {
	return root() . "/src";
}

/**
 * @return string The absolute path to the templates directory
 */
function t(): string {
	return src() . "/templates";
}

/**
 * @return string The absolute path to the secrets directory
 */
function secrets(): string {
	return root() . "/secrets";
}

/**
 * @return string The relative path to the assets directory (from the file where execution started, i.e. the current page)
 */
function assets(): string {
	$cwd            = getcwd();
	$host           = $_SERVER["HTTP_HOST"];
	$adminSubdomain = "admin.";
	if (endsWith($cwd, "/public")) {
		return "assets";
	}
	if (startsWith($host, $adminSubdomain) && !contains($cwd, "/public/")) {
		return "//" . substr($host, strlen($adminSubdomain)) . "/assets";
	}
	return "/assets"; // give up and hope
}

/**
 * Global variables from the above functions
 */
$assets  = assets();
$t       = t();
$src     = src();
$root    = root();
$secrets = secrets();