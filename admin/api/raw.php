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

// This endpoint is for raw asset data. For metadata, use the assets endpoint.
// @todo implement the assets endpoint
endpoint(...[
    'get'          => $reject,
    'get_value'    => function($value) use ($db): Result {
        if (startsWith($value, "cached/")) {
            // @todo Handle cached images in raw api and use in listing editor
            return new Result(501, "Can't read cache");
        }
        $asset = startsWith($value, "stored/") ?
            $db->getAssetByKey(intval(substr($value, strlen("stored/")))) : $db->getAssetByPath($value);
        if ($asset === null) {
            return new Result(404, "Asset $value not found");
        }
        header("Content-Type: " . $asset->getType());
        readfile($asset->absolutePath());
        exit(); // Exit here to avoid outputting JSON
    },
    'delete'       => $reject,
    'delete_value' => $reject,
]);