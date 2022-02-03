<?php
require_once 'api.php';

// Get an imgTag. Useful to preload the cache.
endpoint(...[
		'get' => $reject,
		'get_value' => function($key) use ($db): Result {
			$height = $_GET["height"] ?: 600;
			if (!is_numeric($key)) {
				return new Result(400, error: "Asset key $key must be numeric");
			}
			$asset = $db->getAssetByKey(intval($key));
			if ($asset === null) {
				return new Result(404, error: "Asset $key not found");
			}
			return new Result(200, $asset->imgTag("", false, false, $height));
		},
		'put' => $reject,
		'put_value' => $reject,
		'post' => $reject,
		'post_value' => $reject,
		'delete' => $reject,
		'delete_value' => $reject,
]);
