<?php
/*
 * Copyright 2025 Google LLC
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

require_once "../src/common.php";
require_once "$src/form.php";
require_once "$t/header.php";
require_once "$t/footer.php";
if (isset($_GET["form"])) {
	$form = _G_forms()[strtolower($_GET["form"])];
}
if (!isset($form)) {
	log_err("Unknown form");
	require_once "$src/errors/510.php";
	exit();
}
/* @var $form Form */
?>
<!DOCTYPE html>
<html lang="en-US">
<title>
	<?=$form->title . ' Form - ' . _G_longname()?>
</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="canonical" href="https://<?=_G_public_domain() . '/' . $form->id?>">
<?php
style();
pageHeader();
?>
<h2><?=$form->title?></h2>
<?php
$form->embed();
footer();
?>
</html>