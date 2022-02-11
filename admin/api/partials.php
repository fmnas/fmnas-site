<?php
require_once 'api.php';

endpoint(...[
		'get' => function(): Result {
			if (!chdir('../templates')) {
				return new Result(500, "Failed to chdir into templates");
			}
			$partials = [];
			foreach (glob('*') as $filename) {
				$partials[$filename] = file_get_contents($filename);
			}
			return new Result(200, $partials);
		},
		'put' => $reject,
		'delete' => $reject,
]);
