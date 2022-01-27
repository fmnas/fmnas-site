<?php
// CSS utilities

/**
 * Build a CSS selector by prepending and appending given strings to each of several selectors
 * @param array $selectors A list of selectors (such as "tr.st_1")
 * @param string $append A value to append to each selector (such as ">td")
 * @param string $prepend A value to prepend to each selector (such as "table ")
 * @return string A selector combining the selectors
 */
function buildSelector(array $selectors, string $append = "", string $prepend = ""): string {
	return implode(",",
			array_map(function($sel) use ($prepend, $append) {
				return $prepend . $sel . $append;
			}, $selectors)
	);
}

/**
 * Escape special characters for use in a CSS string
 * @param string $content An unsanitized string
 * @return string Value to put in CSS string
 */
function cssspecialchars(string $content): string {
	$output = "";
	foreach (str_split($content) as $char) {
		if (ctype_alnum($char) || in_array($char, str_split("!@#$%^&*()-_=+[]{}|;:,<.>/?~` "))) {
			// Pass character
			$output .= $char;
		} else {
			if (in_array($char, str_split("\r\t"))) {
				// Discard character
				continue;
			} else {
				// Escape character (hex sequence is terminated by space)
				$output .= "\\" . strtoupper(dechex(mb_ord($char, "UTF-8"))) . ' ';
			}
		}
	}
	return $output;
}