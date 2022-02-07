<?php
/*
 * Copyright 2021 Google LLC
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

require_once 'api.php';
// TODO [$62017cab2d94b9000ab2d96a]: Allow browser to cache raw API GET requests.

$writer = function(string $key, mixed $body) use ($db): Result {
	if(isset($_FILES['file']['tmp_name'])) {
		$body = file_get_contents($_FILES['file']['tmp_name']);
	}
	$asset = $db->getAssetByKey(intval($key));
	if ($asset === null) {
		return new Result(404, error: "Metadata not found for asset $key");
	}
	$path = $asset->absolutePath();
	if (file_exists($path)) {
		return new Result(409, error: "Asset $key already exists");
	}
	if (file_put_contents($path, $body) === false) {
		return new Result(500, error: "Failed to save asset to $path");
	}
	if (startsWith($asset->getType(), "image/")) {
		// Make an (asynchronous) imgTag request to generate cached versions
		$domain = _G_admin_domain();
		$height = $_GET["height"] ?? '';
		/** @noinspection HttpUrlsUsage */
		exec("bash -c 'curl http://$domain/api/tag/{$asset->key}?height=$height > /dev/null 2>&1 &'");
	}
	return new Result(204);
};

// This endpoint is for raw asset data. For metadata, use the assets endpoint.
endpoint(...[
		'get' => $reject,
		'get_value' => function($value) use ($db): Result {
			if (startsWith($value, "cached/")) {
				// TODO [#58]: Handle cached images in raw api and use in listing editor
				return new Result(501, error: "Can't read cache");
			}
			$asset = startsWith($value, "stored/") ?
					$db->getAssetByKey(intval(substr($value, strlen("stored/")))) : $db->getAssetByPath($value);
			if ($asset === null) {
				return new Result(404, error: "Asset $value not found (did you mean stored/$value?)");
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
		'post' => $reject,
		'post_value' => $writer,
		'put' => $reject,
		'put_value' => $reject,
		'delete' => $reject,
		'delete_value' => $reject,
]);
