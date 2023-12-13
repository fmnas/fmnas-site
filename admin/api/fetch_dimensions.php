<?php
/*
 * Copyright 2023 Google LLC
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

endpoint(...[
		'get_value' => function($id) use ($db): Result {
			$asset = $db->getAssetByKey($id);
			if (!$asset) {
				return new Result(404, "asset $id not found");
			}
			if (!$asset->gcs) {
				return new Result(405, "asset $id is local, not gcs");
			}
			$ch = curl_init();
			if (!$ch) {
				return new Result(500, "couldn't init curl");
			}
			curl_setopt($ch, CURLOPT_URL, Config::$image_size_endpoint . '/?' .
					http_build_query(['bucket' => Config::$static_bucket, 'object' => 'stored/' . $asset->key]));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$rt = curl_exec($ch);
			$info = curl_getinfo($ch);
			if (curl_errno($ch)) {
				return new Result(500, curl_error($ch));
			}
			if ($info['http_code'] !== 200) {
				return new Result($info['http_code'], $rt);
			}
			curl_close($ch);
			$result = json_decode($rt, true);
			if (!is_array($result) || !isset($result['height']) || !isset($result['width'])) {
				return new Result(500, $result);
			}
			if ($err = $db->setSize($id, $result['width'], $result['height'])) {
				return new Result(500, $err);
			}
			return new Result(200);
		},
]);
