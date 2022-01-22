<?php
require_once 'api.php';
require_once "$src/generator.php";

endpoint(...[
		'get' => function() use ($db): Result {
			generate();
			return new Result(200);
		},
		'get_value' => $reject,
		'post' => $reject,
		'post_value' => $reject,
		'delete' => $reject,
		'delete_value' => $reject,
]);