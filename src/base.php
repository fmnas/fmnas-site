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

class BaseException extends Exception {
}

/**
 * Apply a given URL base to relative links in a DOMDocument.
 * @param DOMDocument $dom
 * @param string|null $base
 * @param bool $inPlace
 * @return DOMDocument
 * @throws BaseException
 */
function applyBase(DOMDocument $dom, string|null $base = null, bool $inPlace = false): DOMDocument {
	$base ??= '//' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
	try {
		if (!$inPlace) {
			// Clone the DOMDocument to avoid modifying the original.
			$html5 = new HTML5(["disable_html_ns" => true]);
			if (!($html = $dom->saveHTML())) {
				throw new BaseException("Failed to save original DOM to HTML");
			}
			$dom = $html5->loadHTML(trim($html));
		}

		// Find the head.
		$head = $dom->getElementsByTagName('head')[0] ?? $dom->firstElementChild;
		if (!$head) {
			throw new BaseException("Didn't find a head");
		}

		// Inject a base tag.
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

		return $dom;
	} catch (DOMException $e) {
		throw new BaseException("DOM Exception: " . $e->getMessage());
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
