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
 * Remotely minify a DOM.
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


function inlineStyles(DOMDocument $dom, string $pwd): string {
	try {
		$stylesToInject = [];
		foreach (collectElements($dom, "link", attr("rel", "stylesheet")) as $link) {
			/** @var $link DOMElement */
			$href = $link->getAttribute("href");
			$url = parse_url($href);
			// TODO [#74]: Consider solutions for $url["query"] === $_SERVER["HTTP_HOST"]
			// TODO [#75]: Consider solutions for startsWith($url["path"], "/")
			if (!isset($url["host"]) && !startsWith($url["path"], "/")) {
				// Relative path
				ob_start();
				if (isset($url["query"])) {
					parse_str($url["query"], $_GET);
				}
				$file = $pwd . "/" . $url["path"];
				if (!is_file($file)) {
					// Ignore this one
					continue;
				}
				include $file;
				$styles = ob_get_clean();
			} else {
				// Absolute path
				if (!ini_get('allow_url_fopen')) {
					// Can't get the file :(
					continue;
				}
				$path = $url["scheme"] ?? (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http");
				$path .= "://";
				$path .= $url["host"] ?? $_SERVER["HTTP_HOST"];
				if (isset($url["port"])) {
					$path .= ":" . $url["port"];
				}
				$path .= $url["path"];
				if (isset($url["query"])) {
					$path .= "?" . $url["query"];
				}
				$styles = file_get_contents($path);
			}
			if ($styles === false) {
				// Failed to get styles; delegate responsibility to the email client.
				continue;
			}
			$style = $dom->createElement("style");
			$style->setAttribute("type", "text/css");
			$style->setAttribute("data-type", "link");
			copyAttributes($link, $style, "rel", "href");
			$locator = "/* INJECT STYLE HERE: " . sha1($styles) . " */";
			$stylesToInject[$locator] = $styles;
			$style->nodeValue = $locator; // Can't inject right now because we would end up with HTML entities, etc.
			$link->parentNode?->replaceChild($style, $link);
		}

		return str_replace(array_keys($stylesToInject), array_values($stylesToInject), $dom->saveHTML());
	} catch (DOMException $e) {
		return $dom->saveHTML();
	}
}
