<?php
require_once 'api.php';

// This endpoint is for raw asset data. For metadata, use the assets endpoint.
// TODO [#33]: implement the assets endpoint
endpoint(...[
		'get' => $reject,
		'get_value' => function($value) use ($db): Result {
			if (startsWith($value, "cached/")) {
				// TODO [#58]: Handle cached images in raw api and use in listing editor
				return new Result(501, "Can't read cache");
			}
			$asset = startsWith($value, "stored/") ?
					$db->getAssetByKey(intval(substr($value, strlen("stored/")))) : $db->getAssetByPath($value);
			if ($asset === null) {
				return new Result(404, "Asset $value not found");
			}

			// Caching
			$etag = '"' . filemtime(__FILE__) . filemtime($asset->absolutePath()) . '"';
			header("ETag: $etag");
			$ifNoneMatch = array_map('trim', explode(',', trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')));
			if (in_array($etag, $ifNoneMatch, true)) {
				header("HTTP/2 304 Not Modified");
				exit();
			}

			header("Content-Type: " . $asset->getType());
			readfile($asset->absolutePath());
			exit(); // Exit here to avoid outputting JSON
		},
		'delete' => $reject,
		'delete_value' => $reject,
]);
