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

endpoint(...[
		'get' => function(): Result {
			global $_G;
			return new Result(200, $_G);
		},
		'get_value' => function($key): Result {
			global $_G;
			if (isset($_G[$key])) {
				return new Result(200, $_G[$key]);
			}
			return new Result(404, error: "Config key $key not found");
		},
		'put' => $reject,
		'put_value' => function($key, $value) use ($db): Result {
			global $src, $_G;
			if (!isset($_G[$key])) {
				return new Result(404, error: "Config key $key not found");
			}
			if (!is_string($value)) {
				return new Result(400, error: "Supplied value must be a string");
			}
			$error = $db->setConfigValue($key, $value);
			if ($error) {
				return new Result(500, error: $error);
			}
			require_once "$src/generator.php";
			generate();
			return new Result(204);
		},
		'delete' => $reject,
]);
