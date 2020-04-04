<?php

class Dependencies {
	public static function lightncandy(): void {
		// Require lightncandy
		if (!self::checkLightncandy()) {
			self::fetchLightncandy();
		}
		require_once __DIR__ . "/lightncandy/src/loader.php";
	}

	public static function parsedown(): void {
		// Require parsedown
		if (!self::checkParsedown()) {
			self::fetchParsedown();
		}
		require_once __DIR__ . "/parsedown/Parsedown.php";
	}

	public static function update(): void {
		self::fetchLightncandy();
		self::fetchParsedown();
	}

	private static function checkLightncandy(): bool {
		return file_exists(__DIR__ . "/lightncandy");
	}

	private static function checkParsedown(): bool {
		return file_exists(__DIR__ . "/parsedown");
	}

	private static function fetch(string $owner, string $repository, callable $checker): void {
		if ($checker()) {
			return;
		}
		$shellOutput = shell_exec(
			"chmod +x " . __DIR__ . "/fetch_latest_release.sh" .
			__DIR__ . "/fetch_latest_release.sh $owner $repository"
		);
		if (!$checker()) {
			log_err("Failed to fetch $repository: $shellOutput");
			echo "Failed to fetch $repository";
		}
	}

	private static function fetchLightncandy(): void {
		self::fetch("zordius", "lightncandy", "self::checkLightncandy");
	}

	private static function fetchParsedown(): void {
		self::fetch("erusev", "parsedown", "self::checkParsedown");
	}

	private static function rrmdir(string $src): void {
		$dir = opendir($src);
		while (false !== ($file = readdir($dir))) {
			if (($file != '.') && ($file != '..')) {
				$full = $src . '/' . $file;
				if (is_dir($full)) {
					self::rrmdir($full);
				} else {
					unlink($full);
				}
			}
		}
		closedir($dir);
		rmdir($src);
	}
}