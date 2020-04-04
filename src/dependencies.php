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

	private static function fetchLightncandy(): void {
		if (self::checkLightncandy()) {
			return;
		}
		$shellOutput = shell_exec(__DIR__ . "/fetch_latest_release.sh zordius lightncandy");
		if (!self::checkLightncandy()) {
			log_err("Failed to fetch lightncandy: $shellOutput");
			echo "Failed to fetch lightncandy";
		}
	}

	private static function fetchParsedown(): void {
		if (self::checkParsedown()) {
			return;
		}
		$shellOutput = shell_exec(__DIR__ . "/fetch_latest_release.sh erusev parsedown");
		if (!self::checkParsedown()) {
			log_err("Failed to fetch parsedown: $shellOutput");
			echo "Failed to fetch parsedown";
		}
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