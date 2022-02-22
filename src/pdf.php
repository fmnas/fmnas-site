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

class PdfException extends Exception {
}

// TODO [#274]: Google Cloud authentication for remote functions
// https://github.com/googleapis/google-auth-library-php
// https://cloud.google.com/docs/authentication/production#auth-cloud-implicit-php

/**
 * Render a DOMDocument to a PDF.
 * @param DOMDocument $original DOM to render to a PDF
 * @param string $target File to which to save the rendered PDF
 * @param ?string $base Base URL to use for relative hrefs
 * @param ?string $margin Margin for the root element
 * @return void
 * @throws PdfException
 */
function renderPdf(DOMDocument $original, string $target, string|null $base = null, string|null $margin = null): void {
	$base ??= (pathinfo($_SERVER['REQUEST_URI'])['dirname'] ?? '.') . '/';
	try {
		$dom = applyBase($original, $base);

		if ($margin) {
			$root = $dom->getElementsByTagName('html')[0] ?? $dom->firstElementChild;
			$style = $root?->hasAttribute("style") ? $root?->getAttribute("style") : "";
			if ($style && !str_ends_with($style, ';')) {
				$style .= ';';
			}
			$style .= "margin: $margin;";
			$root?->setAttribute("style", $style);
		}

		$file = tempnam(sys_get_temp_dir(), "HTM");
		if (!$html = $dom->saveHTML()) {
			throw new PdfException("Error generating HTML");
		}
		if (!file_put_contents($file, $html)) {
			throw new PdfException("Error writing temp file $file");
		}

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
			curl_close($curl);
			throw new PdfException("cURL Error: " . curl_error($curl) . "\n$pdf");
		}
		if (($code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE)) !== 200) {
			curl_close($curl);
			throw new PdfException("Got response code $code\n$pdf");
		}
		curl_close($curl);
		if (!file_put_contents($target, $pdf)) {
			throw new PdfException("Error writing file $file");
		}
	} catch (BaseException $e) {
		throw new PdfException("Error adding base: " . $e->getMessage());
	}
}
