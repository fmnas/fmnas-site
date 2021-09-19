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

// Caching
$etag = '"' . filemtime(__FILE__) . '"';
header("ETag: $etag");
$ifNoneMatch = array_map('trim', explode(',', trim($_SERVER['HTTP_IF_NONE_MATCH'])));
if (in_array($etag, $ifNoneMatch, true)) {
    header("HTTP/2 304 Not Modified");
    exit();
}

header("Content-Type: image/svg+xml");
?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 5 5">
    <path d="M2 0H3V2H5V3H3V5H2V3H0V2H2" fill="#<?=$_GET["color"]?>"/>
</svg>