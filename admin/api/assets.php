<?php
/*
 * Copyright 2022 Google LLC
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
