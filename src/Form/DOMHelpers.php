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


namespace fmnas\Form;

use Closure;
use DOMDocument;
use DOMElement;
use JetBrains\PhpStorm\Pure;

class DOMHelpers {
	/**
	 * Copy all attributes from one DOMElement to another.
	 * @param DOMElement $from The element that has the attributes.
	 * @param DOMElement $to The element that wants the attributes.
	 * @param string ...$exclude A list of attributes not to copy.
	 * @return void
	 */
	public static function copyAttributes(DOMElement $from, DOMElement $to, string ...$exclude): void {
		if ($from->hasAttributes()) {
			foreach ($from->attributes as $attribute) {
				if (!in_array($attribute->nodeName, $exclude)) {
					$to->setAttribute($attribute->nodeName, $attribute->nodeValue);
				}
			}
		}
	}

	/**
	 * Move all children from one DOMElement to another.
	 * @param DOMElement $from The element that has the children.
	 * @param DOMElement $to The element that wants the children.
	 * @return void
	 */
	public static function moveChildren(DOMElement $from, DOMElement $to): void {
		while ($from->firstChild) {
			$to->appendChild($from->firstChild);
		}
	}

	/**
	 * Build an array of elements within a DOMElement or DOMDocument with the given tag that match the filter.
	 * By default, all tags are matched and no filter is applied.
	 * Elements with a truthy data-ignore are never returned.
	 * Using getElementsByTagName directly in a foreach loop means you can't remove elements, since this messes up the
	 * internal iterator. This way you can remove elements while iterating.
	 * @param DOMElement|DOMDocument $root The root element to search.
	 * @param string $tag The tag to match.
	 * @param Closure|null $filter A filter (DOMElement -> bool) to apply to elements before adding them to the array.
	 * @return array An array containing elements.
	 * @noinspection PhpUnusedParameterInspection
	 */
	public static function collectElements(DOMElement|DOMDocument $root, string $tag = "*",
			?Closure $filter = null): array {
		$filter ??= function(DOMElement $element): bool {
			return true;
		};
		$elements = [];
		foreach ($root->getElementsByTagName($tag) as $element) {
			/** @var $element DOMElement */
			if ($filter($element) && !self::checkString($element->getAttribute("data-ignore"))) {
				$elements[] = $element;
			}
		}
		if ($root instanceof DOMElement &&
				($root->tagName === "form" || ($root->tagName === "article" && $root->getAttribute("data-type") === "form")) &&
				$root->getAttribute("id")) {
			$elementsOutsideForm =
					self::collectElements($root->ownerDocument, $tag, function(DOMElement $element) use ($root, $filter): bool {
						return $element->getAttribute("form") === $root->getAttribute("id") && $filter($element);
					});
			array_push($elements, ...$elementsOutsideForm);
		}
		return $elements;
	}

	/**
	 * Check truthiness of a string. Defined the same as in PHP, except "false" is falsy.
	 * TODO [#72]: Make "" truthy (doesn't seem to work?)
	 * @param string $str A string
	 * @return bool Whether the string is truthy.
	 */
	#[Pure] public static function checkString(string $str): bool {
		return $str && $str !== "false";
	}

	/**
	 * Generates a closure that returns true if the given element has any of the given attributes.
	 * @param string ...$attributes Attributes to check
	 * @return Closure to be passed to collectElements
	 */
	#[Pure] public static function has(string ...$attributes): Closure {
		return function(DOMElement $element) use ($attributes): bool {
			foreach ($attributes as $attribute) {
				if ($element->hasAttribute($attribute)) {
					return true;
				}
			}
			return false;
		};
	}

	/**
	 * Generates a closure that returns true if the given attribute on the given element is truthy.
	 * @param string $attribute An attribute to check
	 * @return Closure to be passed to collectElements
	 */
	#[Pure] public static function truthy(string $attribute): Closure {
		return function(DOMElement $element) use ($attribute): bool {
			return self::checkString($element->getAttribute($attribute));
		};
	}

	/**
	 * Generates a closure that returns true if the given attribute on the given element equals the given value.
	 * @param string $attribute An attribute to check
	 * @param string $value A value against which to compare the attribute
	 * @return Closure to be passed to collectElements
	 */
	#[Pure] public static function attr(string $attribute, string $value): Closure {
		return function(DOMElement $element) use ($attribute, $value): bool {
			return $element->getAttribute($attribute) === $value;
		};
	}

	/**
	 * Negates a filter.
	 * @param Closure $filter A filter (DOMElement -> bool) to apply to elements before adding them to the array.
	 * @return Closure The negation of the given filter.
	 */
	#[Pure] public static function not(Closure $filter): Closure {
		return function(DOMElement $element) use ($filter): bool {
			return !$filter($element);
		};
	}
}
