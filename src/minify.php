<?php
/*
 * Copyright 2022 Google LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once __DIR__ . '/../secrets/config.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/base.php';

use Masterminds\HTML5;

class MinifyException extends Exception {
}

// TODO [#274]: Google Cloud authentication for remote functions
// https://github.com/googleapis/google-auth-library-php
// https://cloud.google.com/docs/authentication/production#auth-cloud-implicit-php

/**
 * Remotely minify an HTML string.
 * @param string $html HTML to minify
 * @param ?string $base Base URL to use for relative hrefs
 * @return string the minified HTML
 * @throws MinifyException
 */
function remoteMinify(string $html, string|null $base = null): string {
	try {
		$html5 = new HTML5(["disable_html_ns" => true]);
		$dom = $html5->loadHTML($html);
		applyBase($dom, $base, true);

		$file = tempnam(sys_get_temp_dir(), "HTM");
		if (!$html = $dom->saveHTML()) {
			throw new MinifyException("Error generating HTML");
		}
		if (!file_put_contents($file, $html)) {
			throw new MinifyException("Error writing temp file $file");
		}

		$curl = curl_init();
		if (!$curl) {
			throw new MinifyException("Failed to initialize cURL");
		}
		curl_setopt_array($curl, [
				CURLOPT_URL => Config::$minify_html_endpoint,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => ["file" => new CURLFile($file)],
				CURLOPT_RETURNTRANSFER => true,
		]);
		if (!($html = curl_exec($curl)) || curl_errno($curl)) {
			curl_close($curl);
			throw new MinifyException("cURL Error: " . curl_error($curl) . "\n$html");
		}
		if (($code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE)) !== 200) {
			curl_close($curl);
			throw new MinifyException("Got response code $code\n$html");
		}
		curl_close($curl);
		return $html;
	} catch (BaseException $e) {
		throw new MinifyException("Error applying base: " . $e->getMessage());
	}
}
