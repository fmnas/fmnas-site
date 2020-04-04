<?php
declare(strict_types = 1);
@header("Content-Encoding: UTF-8");
ini_set("pcre.jit", "0");

function startsWith(string $haystack, $needle): bool {
	if (is_array($needle)) {
		foreach ($needle as $item) {
			if (startsWith($haystack, $item)) {
				return true;
			}
		}
		return false;
	}
	return substr_compare($haystack, $needle, 0, strlen($needle)) === 0;
}

function endsWith(string $haystack, $needle): bool {
	if (is_array($needle)) {
		foreach ($needle as $item) {
			if (endsWith($haystack, $item)) {
				return true;
			}
		}
		return false;
	}
	return substr_compare($haystack, $needle, -strlen($needle)) === 0;
}

function contains(string $haystack, $needle): bool {
	if (is_array($needle)) {
		foreach ($needle as $item) {
			if (contains($haystack, $item)) {
				return true;
			}
		}
		return false;
	}
	return strpos($haystack, $needle) !== false;
}

function validateIdentifier(string $id): bool {
	return strlen($id) > 0 &&
		   ctype_alnum(str_replace("_", "", $id)) &&
		   (ctype_alnum($id[0]) || $id[0] === "_");
}

/**
 * @return string The absolute path to the src directory
 */
function src(): string {
	return __DIR__;
}

/**
 * @return string The absolute path to the site root directory (containing admin/, public/, secrets/, src/)
 */
function root(): string {
	return dirname(src(), 1);
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
	if (contains($cwd, "/public")) {
		return "/assets";
	}
	if (startsWith($host, $adminSubdomain) && !contains($cwd, "/public/")) {
		return "//" . substr($host, strlen($adminSubdomain)) . "/assets";
	}
	return "/assets"; // give up and hope
}

/**
 * @return string The absolute path to the stored assets directory
 */
function stored_assets(): string {
	return root() . "/public/assets/stored";
}

/**
 * Global variables from the above functions
 */
$assets  = assets();
$t       = t();
$src     = src();
$root    = root();
$secrets = secrets();

/**
 * Dummy definitions for use by PhpStorm
 */
$root    ??= "..";
$src     ??= "$root/src";
$t       ??= "$src/templates";
$secrets ??= "$root/secrets";

// Generate and load the generated source with constants from database
if (!file_exists("$src/generated.php")) {
	require_once "$src/generator.php";
	generate();
}
require_once "$src/generated.php";

/**
 * Import a stylesheet
 * @param string $name The relative path to the stylesheet file, optionally including .css or .php
 */
function style(string $name = "/common"): void {
	if (!startsWith($name, "/")) {
		$name = "/" . $name;
	}
	if (!endsWith($name, ".css") && !endsWith($name, ".php")) {
		$name .= ".css";
	}
	echo "<link rel=\"stylesheet\" href=\"" . htmlspecialchars($name) . "\">";
}

/**
 * Logger
 * @param $msg string Message to log, along with backtrace
 */
function log_err(string $msg = "") {
	file_put_contents(root() . "/log", $msg . "\nBacktrace:\n" . print_r(debug_backtrace(), true) . "\n\n", FILE_APPEND);
}

/**
 * Include the email links script
 */
function emailLinks(): void {
	echo '<script src="/email.js"></script>';
}