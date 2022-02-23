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

require_once __DIR__ . "/../../src/resize.php";
$dir = __DIR__ . "/received";

// TODO [$621587bc982c03000afca1bf]: Allow chunked filepond uploads.
switch ($_SERVER['REQUEST_METHOD']) {
case 'POST':
	header("Content-Type: text/plain");
	$new = tempnam($dir, "tmp_");
	$old = $_FILES["images"]["tmp_name"][0];
	if (is_uploaded_file($_FILES["images"]["tmp_name"][0])) {
		if (!str_starts_with($_FILES["images"]["type"][0], "image/")) {
			move_uploaded_file($_FILES["images"]["tmp_name"][0], $new);
		} else {
			try {
				resize($old, $new, 8640);
			} catch (ImageResizeException $e) {
				move_uploaded_file($_FILES["images"]["tmp_name"][0], $new);
			}
		}
	} else {
		http_response_code(400);
		die();
	}
	echo explode("tmp_", $new)[1] . ':' . $_FILES["images"]["name"][0];
	break;
case 'GET':
	$raw = trim(file_get_contents('php://input'));
	if (preg_match('/^[^/:]+:[^/]+$/', $raw)) {
		$filename = implode(':', array_slice(explode(':', $raw), 1));
		$local = "$dir/tmp_" . explode(':', $raw)[0];
		if (!file_exists($local)) {
			http_response_code(404);
			die();
		}
		header("Content-Disposition: inline; filename=\"$filename\"");
		header("Content-Type: " . mime_content_type($local));
		echo file_get_contents($local);
	} else {
		// fetch
		$contents = file_get_contents($raw);
		if ($contents === false) {
			http_response_code(404);
			die();
		}
		header("Content-Type: text/plain");
		$old = tempnam($dir, "tmp_");
		$new = tempnam($dir, "tmp_");
		if (str_starts_with(mime_content_type($old), "image/")) {
			try {
				resize($old, $new, 8640);
				unlink($old);
				echo explode("tmp_", $new)[0] . ':' . pathinfo($raw, PATHINFO_FILENAME);
			} catch (ImageResizeException $e) {
				@unlink($new);
				echo explode("tmp_", $old)[0] . ':' . pathinfo($raw, PATHINFO_FILENAME);
			}
		} else {
			@unlink($new);
			echo explode("tmp_", $old)[0] . ':' . pathinfo($raw, PATHINFO_FILENAME);
		}
	}
	break;
case 'DELETE':
	$raw = trim(file_get_contents('php://input'));
	if (@unlink("$dir/tmp_" . explode(':', $raw)[0])) {
		http_response_code(204);
	} else {
		http_response_code(404);
	}
}
