<?php

class Dependencies {
	public static function lightncandy(): void {
		// Require lightncandy
		if (!self::checkLightncandy()) {
			self::fetchLightncandy();
		}
		// TODO: require it
	}

	public static function parsedown(): void {
		// Require parsedown
		if (!self::checkParsedown()) {
			self::fetchParsedown();
		}
		// TODO: require it
	}

	public static function update(): void {
		// Update lightncandy
		if (!self::checkLightncandy()) {
			echo "Lightncandy not detected.";
			self::fetchLightncandy();
			echo "Fetched lightncandy.";
			return;
		}
		// TODO: check for update
		rename(__DIR__ . "/lightncandy", __DIR__ . "/lightncandy~");
		self::fetchLightncandy();
		if (!self::checkLightncandy()) {
			// Rollback
			self::rrmdir(__DIR__ . "/lightncandy");
			rename(__DIR__ . "/lightncandy~", __DIR__ . "/lightncandy");
			fwrite(STDERR, "Failed to update lightncandy!");
		} else {
			echo "Successfully updated lightncandy.";
		}
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
		// TODO: fetch lightncandy
	}

	private static function fetchParsedown(): void {
		if (self::checkParsedown()) {
			return;
		}
		// TODO: fetch parsedown
	}

	private static function rrmdir($src): void {
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