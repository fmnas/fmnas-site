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

use JetBrains\PhpStorm\Pure;
use Masterminds\HTML5;

	/** @noinspection PhpIncludeInspection */
require_once root() . "/vendor/autoload.php";

class PdfException extends Exception {
}

// TODO [#274]: Google Cloud authentication for remote functions
// https://github.com/googleapis/google-auth-library-php
// https://cloud.google.com/docs/authentication/production#auth-cloud-implicit-php

/**
 * Render a DOMDocument to a PDF.
 * @param DOMDocument $original DOM to render to a PDF
 * @param string $target File to which to save the rendered PDF
 * @param ?string $base Base URL to use for relative hrefs (defaults to $_SERVER['REQUEST_URI'])
 * @return void
 * @throws PdfException
 */
function renderPdf(DOMDocument $original, string $target, string|null $base): void {
	$base ??= (pathinfo($_SERVER['REQUEST_URI'])['dirname'] ?? '.') . '/';
	try {
		// Clone the DOMDocument to avoid modifying the original.
		$html5 = new HTML5(["disable_html_ns" => true]);
		if (!($html = $original->saveHTML())) {
			throw new PdfException("Failed to save original DOM to HTML");
		}
		$dom = $html5->loadHTML($html);

		// Find the head.
		$head = $dom->getElementsByTagName('head')[0] ?? $dom->firstElementChild;
		if (!$head) {
			throw new PdfException("Didn't find a head");
		}

		// Inject a base tag, just in case.
		$baseEl = $dom->createElement('base');
		$baseEl->setAttribute('href', $base);
		$head->prepend($baseEl);

		foreach($dom->getElementsByTagName("*") as $el) {
			/** @var $el DOMElement */
			if ($el->hasAttribute("href")) {
				$el->setAttribute("href", addBase($el->getAttribute("href"), $base));
			}
			if ($el->hasAttribute("src")) {
				$el->setAttribute("src", addBase($el->getAttribute("src"), $base));
			}
			if ($el->hasAttribute("srcset")) {
				$srcset = explode(",", $el->getAttribute("srcset"));
				foreach ($srcset as &$src) {
					$components = explode(" ", trim($src));
					$components[0] = addBase($components[0], $base);
					$src = implode(" ", $components);
				}
				$el->setAttribute("srcset", implode(",", $srcset));
			}
		}

		$file = tempnam(sys_get_temp_dir(), "HTM");
		if (!$html = $dom->saveHTML()) {
			throw new PdfException("Error generating HTML");
		}
		if (!file_put_contents($file, $html)) {
			throw new PdfException("Error writing temp file $file");
		}

		var_dump($html);

		$curl = curl_init();
		if (!$curl) {
			throw new PdfException("Failed to initialize cURL");
		}
		curl_setopt_array($curl, [
				CURLOPT_URL => Config::$print_pdf_endpoint,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => ["file" => new CURLFile($file)],
				CURLOPT_RETURNTRANSFER => true,
		]);
		if (!($pdf = curl_exec($curl)) || curl_errno($curl)) {
			throw new PdfException("cURL Error: " . curl_error($curl) . "\n$pdf");
		}
		curl_close($curl);
		if (!file_put_contents($target, $pdf)) {
			throw new PdfException("Error writing file $file");
		}
	} catch (DOMException $e) {
		throw new PdfException("DOM Exception: " . $e->getMessage());
	}
}

#[Pure] function addBase(string $href, string $base): string {
	$parsedHref = parse_url($href);
	if (isset($parsedHref['scheme']) || isset($parsedHref['host']) || $href[0] === '#') {
		return $href;
	}

	if ($href[0] === '/') {
		$parsedBase = parse_url($base);
		$baseDomain = '//' . ($parsedBase['host'] ?? '');
		if (isset($parsedBase['scheme'])) {
			$baseDomain = $parsedBase['scheme'] . ":$baseDomain";
		}
		return "$baseDomain$href";
	}

	$baseInfo = pathinfo($base);
	$basePath = $baseInfo['dirname'] ?? '.';
	if (str_ends_with($base, '/')) {
		$basePath .= '/' . $baseInfo['basename'];
	}
	if (str_ends_with($basePath, '/')) {
		$basePath = substr($basePath, 0, -1);
	}
	return "$basePath/$href";
}
