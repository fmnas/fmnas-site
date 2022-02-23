<?php
declare(strict_types = 1);
@header("Content-Encoding: UTF-8");
@header("Accept-CH: Sec-CH-UA, Sec-CH-UA-Mobile, Sec-CH-UA-Platform, Viewport-Width, Device-Memory, Downlink");
ini_set("pcre.jit", "0");
set_include_path(__DIR__);
include 'common-test.php';
require_once __DIR__ . "/../vendor/autoload.php";

use JetBrains\PhpStorm\Pure;

#[Pure] function startsWith(string $haystack, $needle): bool {
	if (is_array($needle)) {
		foreach ($needle as $item) {
			if (startsWith($haystack, $item)) {
				return true;
			}
		}
		return false;
	}
	return str_starts_with($haystack, $needle);
}

#[Pure] function endsWith(string $haystack, $needle): bool {
	if (is_array($needle)) {
		foreach ($needle as $item) {
			if (endsWith($haystack, $item)) {
				return true;
			}
		}
		return false;
	}
	return str_ends_with($haystack, $needle);
}

#[Pure] function contains(string $haystack, $needle): bool {
	if (is_array($needle)) {
		foreach ($needle as $item) {
			if (contains($haystack, $item)) {
				return true;
			}
		}
		return false;
	}
	return str_contains($haystack, $needle);
}

#[Pure] function validateIdentifier(string $id): bool {
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
#[Pure] function root(): string {
	return dirname(src(), 1);
}

/**
 * @return string The absolute path to the templates directory
 */
#[Pure] function t(): string {
	return src() . "/templates";
}

/**
 * @return string The absolute path to the secrets directory
 */
#[Pure] function secrets(): string {
	return root() . "/secrets";
}

/**
 * @return string The relative path to the assets directory (from the file where execution started, i.e. the current
 *     page)
 */
#[Pure] function assets(): string {
	$host = $_SERVER["HTTP_HOST"];
	$adminSubdomain = "admin.";
	if (startsWith($host, $adminSubdomain)) {
		return "//" . substr($host, strlen($adminSubdomain)) . "/assets";
	}
	return "/assets";
}

/**
 * @return string The absolute path to the stored assets directory
 */
#[Pure] function stored_assets(): string {
	return root() . "/public/assets/stored";
}

/**
 * @return string The absolute path to the cached assets directory
 */
#[Pure] function cached_assets(): string {
	return root() . "/public/assets/cache";
}

/**
 * Global variables from the above functions
 */
$assets = assets();
$t = t();
$src = src();
$root = root();
$secrets = secrets();

/**
 * Dummy definitions for use by IntelliJ
 */
$root ??= __DIR__ . "..";
$src ??= "$root/src";
$t ??= "$src/templates";
$secrets ??= "$root/secrets";

require_once "$secrets/config.php";

/**
 * Generate and load the generated source with constants from database.
 * This can"t be moved to a function because it uses the globals and PHP is PHP.
 */
if (!file_exists("$src/generated.php")) {
	require_once "$src/generator.php";
	generate();
}
require_once "$src/generated.php";

/**
 * Import a stylesheet
 * @param string $name The relative path to the stylesheet file, optionally including .css or .php
 * @param bool $relative Do not output a leading slash in the href.
 * @param string|null $buster A cachebuster to use.
 */
function style(string $name = "/common", bool $relative = false, ?string $buster = null): void {
	global $root;
	if (!startsWith($name, "/") && !$relative) {
		$name = "/" . $name;
	}
	if (!endsWith($name, ".css") && !endsWith($name, ".php")) {
		$name .= ".css";
	}
	if ($buster !== null) {
		$name .= "?buster=$buster";
	} else if (!$relative) {
		$name .= "?buster=" . filemtime("$root/public$name");
	}
	echo "<link rel=\"stylesheet\" href=\"" . htmlspecialchars($name) . "\">";
}

/**
 * Logger
 * @param $msg string Message to log, along with backtrace
 */
function log_err(string $msg = "") {
	error_log($msg);
	file_put_contents(root() . "/log",
			date("c\n") . $msg . "\nBacktrace:\n" . print_r(debug_backtrace(), true) . "\n\n", FILE_APPEND);
}

/**
 * Include the email links script
 */
function emailLinks(): void {
	echo '<script src="/email.js.php"></script>';
}

/**
 * Launch a background process (for mod-fcgi only)
 */
function launch(string $command): void {
	$pipes = [];
	proc_close(proc_open("$command &", [], $pipes));
}

function requestLogHeaders() {
	$domain = _G_public_domain();
	$tempfile = @tempnam(sys_get_temp_dir(), "HEAD");
	if (file_put_contents($tempfile, serialize([
			"server" => $_SERVER,
			"headers" => getallheaders(),
	]))) {
		$credentials = Config::$api_credentials;
		/** @noinspection HttpUrlsUsage */
		launch("curl -v -u \"$credentials\" -F 'file=$tempfile' http://$domain/log_headers.php");
	}
}
register_shutdown_function('requestLogHeaders');
