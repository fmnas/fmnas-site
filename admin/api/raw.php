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

use JetBrains\PhpStorm\NoReturn;

require_once 'api.php';
require_once "$src/resize.php";

// This endpoint is for raw asset data. For metadata, use the assets endpoint.

$writer = function(string $key, mixed $body) use ($db): Result {
	if(isset($_FILES['file'])) {
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
		// Find the size of the image.
		try {
			$size = size($path);
			$arr = [
					"path" => $asset->path,
					"data" => $asset->data,
					"type" => $asset->type,
					"width" => $size[0],
					"height" => $size[1],
                    "gcs" => false,
			];
			$db->updateAsset($key, $arr);
		} catch (ImageResizeException $e) {
			log_err(print_r($e, true));
		}

		// Make an (asynchronous) imgTag request to generate cached versions
		$domain = _G_admin_domain();
		$height = $_GET["height"] ?? '';
		$credentials = Config::$api_credentials;
		/** @noinspection HttpUrlsUsage */
		launch("curl -u '$credentials' http://$domain/api/tag/{$asset->key}?height=$height");
		// Thumbnails for admin
		/** @noinspection HttpUrlsUsage */
		launch("curl -u '$credentials' http://$domain/api/tag/{$asset->key}?height=64&expand=0");
		/** @noinspection HttpUrlsUsage */
		launch("curl -u '$credentials' http://$domain/api/tag/{$asset->key}?height=192&expand=0");
	}
	return new Result(204);
};

#[NoReturn] function returnFile(string $filename, string $type): void {
	// Caching
	$etag = '"' . filemtime(__FILE__) . filemtime($filename) . '"';
	header("ETag: $etag");
	$ifNoneMatch = array_map('trim', explode(',', trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')));
	if (in_array($etag, $ifNoneMatch, true)) {
		header("HTTP/2 304 Not Modified");
		exit();
	}

	header("Content-Type: " . $type);
	readfile($filename);
	exit(); // Exit here to avoid outputting JSON
}

endpoint(...[
		'get' => $reject,
		'get_value' => function($value) use ($db): Result {
			if (startsWith($value, "cached/")) {
				if (endsWith($value, ".html")) {
					return new Result(501, error: "Reading cached descriptions not implemented");
				}
				$info = explode('_', basename($value, ".jpg"));
				if (count($info) !== 2) {
					return new Result(400, $info, error: "Failed to parse request info");
				}
				$key = $info[0];
				$height = $info[1];
                $asset = $db->getAssetByKey($key);
                if ($asset->gcs) {
                    if ($height >= $asset->size()[1]) {
                        return new Result(302, 'https://'.Config::$static_domain . "/stored/$key");
                    }
                    return new Result(301, 'https://' . Config::$static_domain . "/cache/${key}_$height.jpg");
                }
                $filename = cached_assets() . "/${key}_$height.jpg";
				if (file_exists($filename)) {
					returnFile($filename, "image/jpeg");
				}
				returnFile(root() . "/public" . $asset->cachedImage($height), "image/jpeg");
			}
			$asset = startsWith($value, "stored/") ?
					$db->getAssetByKey(intval(substr($value, strlen("stored/")))) : $db->getAssetByPath($value);
			if ($asset === null) {
				return new Result(404, error: "Asset $value not found (did you mean stored/$value?)");
			}
            if ($asset->gcs) {
                return new Result(301, 'https://' . Config::$static_domain . '/stored/' . $asset->key);
            }
			returnFile($asset->absolutePath(), $asset->getType());
		},
		'post' => $reject,
		'post_value' => $writer,
		'put' => $reject,
		'put_value' => $writer,
		'delete' => $reject,
		'delete_value' => $reject,
]);
