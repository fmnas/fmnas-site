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

endpoint(...[
		'get' => function() use ($db): Result {
			try {
				try {
					$db->query("alter table assets add column width mediumint");
					$db->query("alter table assets add column height mediumint");
				} catch (Exception $e) {

				}
				$assets = $db->query("select * from assets where type like 'image/%' and height is null");
				foreach ($assets as $ass) {
					$asset = $db->getAssetByKey($ass['id']);
					if (!$asset) {
						var_dump($asset);
					}
					$size = $asset?->size();
					if ($size) {
						$db->query("update assets set width=${size[0]}, height=${size[1]} where id={$ass['id']}");
					}
				}
			} catch (Exception $e) {
				return new Result(500, error: $e);
			}
			return new Result(200);
		},
]);
