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

require_once '../src/common.php';

use fmnas\Form\FormConfig;

if (@$_GET['input']) {
	echo '<pre>' . $_GET['input'] . "\n\n";
	$contents = unserialize(file_get_contents('../public/application/received/' . $_GET['input']));
	var_dump($contents);
	/** @noinspection PhpObjectFieldsAreOnlyWrittenInspection */
	$formConfig = new FormConfig();
	$formConfig->returnEarly = false;
	$formConfig->debug = true;
	$DEDUPLICATE = false;
	$_POST = $contents;
	require_once '../public/application/index.php';
}

// TODO [$62904b8f0182cf0009704b1a]: The serialized files don't get deleted after resending failed applications for some reason.

?>

<!DOCTYPE html>
<title>Resend Application</title>
<meta charset="utf-8">
<meta name="robots" content="noindex,nofollow">
<form method="GET">
	<label>Serialized filename: <input type="text" name="input" placeholder="1653613104.8196.serialized" required></label><br>
	<input type="submit">
</form>

