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
require_once "../../src/common.php";
require_once "$src/resize.php";

endpoint(...[
		'get' => function() use ($db): Result {
			set_time_limit(0);
			echo "{\n";
			$succeeded = 0;
			$failed = 0;
			foreach (glob(cached_assets() . "/*.jpg") as $filename) {
				$components = explode("_",basename($filename));
				$original = stored_assets() . "/${components[0]}";
				$height = intval(explode(".",$components[1])[0]);
				try {
					resize($original, $filename, $height);
				} catch (ImageResizeException $e) {
					echo "\"$filename\": " . json_encode($e) . ",\n";
					$failed++;
				}
				echo "\"$filename\": \"Success\",\n";
				$succeeded++;
			}
			echo "\"succeeded\": $succeeded, \"failed\": $failed}\n";
			exit(0);
		},
		'get_value' => $reject,
		'post' => $reject,
		'post_value' => $reject,
		'delete' => $reject,
		'delete_value' => $reject,
]);
