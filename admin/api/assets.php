<?php
require_once 'api.php';

// This endpoint is for asset metadata. For raw data, use the raw endpoint.
endpoint(...[
		'get' => $reject,
		'get_value' => function($key) use ($db): Result {
			if (!is_numeric($key)) {
				return new Result(400, error: "Asset key $key must be numeric");
			}
			$asset = $db->getAssetByKey(intval($key));
			if ($asset === null) {
				return new Result(404, error: "Asset $key not found");
			}
			return new Result(200, $asset);
		},
		'put' => $reject,
		'put_value' => function($key, $value) use ($db): Result {
			if ($db->getAssetByKey(intval($key)) === null) {
				return new Result(404, error: "Asset $key not found");
			}
			$error = $db->updateAsset(intval($key), $value);
			if ($error) {
				return new Result(500, error: $error);
			}
			return new Result(204);
		},
		'post' => function($value) use ($db): Result {
			$key = $db->insertAsset($value);
			if (is_string($key)) {
				return new Result(500, error: $key);
			}
			return new Result(200, $db->getAssetByKey($key));
		},
		'post_value' => $reject,
		'delete' => $reject,
]);
