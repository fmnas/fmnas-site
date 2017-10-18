<!DOCTYPE html>
<?php
	require_once($_SERVER['DOCUMENT_ROOT'].'/includes/paths.php');
	require_once("$BASE/includes/db.php");

	if($_GET["petkey"]) $pet = retrieve_pet_from_key($_GET["petkey"]);
	elseif($_GET["pet"]) $pet = retrieve_pet_from_concat($_GET["pet"]);
	else $pet = new_pet();
	var_dump($pet);
?>
<html>
	<head>
		<title>Photo upload</title>
		<meta charset="UTF-8">

		<!-- Jquery -->
		<script src="<?=$jquery_path?>"></script>

		<!-- jQuery UI -->
		<link rel="stylesheet" href="<?=$jquery_ui_css_path?>">
		<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>

		<!-- Style -->
		<link rel="stylesheet" type="text/css" href="/<?=$document_root?>includes/text.css">
		<link rel="stylesheet" type="text/css" href="/<?=$document_root?>includes/header.css">
		<link rel="stylesheet" type="text/css" href="/<?=$document_root?>includes/footer.css">

		<!-- BlueImp uploader -->
		<script src="<?=$blueimp_path?>"></script>
		<script src="<?=$blueimp_process_path?>"></script>
		<script src="<?=$blueimp_ui_path?>"></script>
		<script src="<?=$blueimp_jquery_ui_path?>"></script>
	</head>
	<body>
		<form action="upload_handler.php" method="POST">
			<input type="text" name="pet_key" value="<?=$pet['petkey']?>">
			<input type="submit" value="Save changes">
		</form>
	</body>
</html>
