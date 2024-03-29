<?php
require_once __DIR__ . "/common.php";

/**
 * Parse with Handlebars and Parsedown
 * @param string $raw Handlebars markdown
 * @param array $context Context to pass to handlebars parser
 * @return string HTML code
 */
function parse(string $raw, array $context): string {
	$Parsedown = new Parsedown();
	$Parsedown->setBreaksEnabled(true);
	return $Parsedown->text(
			LightnCandy\LightnCandy::prepare(
					LightnCandy\LightnCandy::compile($raw, [
							"partialresolver" => function($cx, $name) {
								$TEMPLATE_DIR = root() . "/admin/templates";
								if (file_exists("$TEMPLATE_DIR/$name")) {
									return file_get_contents("$TEMPLATE_DIR/$name");
								} else {
									log_err("Partial $name not found");
									return "<!-- ERROR: Handlebars partial $name not found! -->";
								}
							},
							"flags" => LightnCandy\LightnCandy::FLAG_NAMEDARG |
									LightnCandy\LightnCandy::FLAG_ERROR_EXCEPTION |
									LightnCandy\LightnCandy::FLAG_HANDLEBARS |
									LightnCandy\LightnCandy::FLAG_RUNTIMEPARTIAL,
					])
			)($context)
	);
}
