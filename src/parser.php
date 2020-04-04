<?php
require_once __DIR__ . "/common.php";
require_once __DIR__ . "/dependencies.php";
Dependencies::parsedown();
Dependencies::lightncandy();


/**
 * Parse with Handlebars and Parsedown
 * @param string $raw Handlebars markdown
 * @return string HTML code
 */
function parse(string $raw): string {
	$Parsedown = new Parsedown();
	return $Parsedown->text(
		LightnCandy::prepare(
			LightnCandy::compile($raw, [
				"partialresolver" => function($cx, $name) {
					$TEMPLATE_DIR = root() . "/admin/templates";
					if (file_exists("$TEMPLATE_DIR/$name")) {
						return file_get_contents("$TEMPLATE_DIR/$name");
					} else {
						log_err("Partial $name not found");
						return "<!-- ERROR: Handlebars partial $name not found! -->";
					}
				}
			])
		)()
	);
}