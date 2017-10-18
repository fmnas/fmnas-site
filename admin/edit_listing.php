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
		<title>Listing editor for <?=$pet['name']?:'new pet'.' - '.$shelter_name?></title>
		<meta charset="UTF-8">

		<!-- Jquery -->
		<script src="<?=$jquery_path?>"></script>

		<!-- TinyMCE -->
		<script src="<?=$tinymce_path?>"></script>
		<script src="<?=$tinymce_jquery_path?>"></script>
		<?php foreach($tinymce_plugins as $plugin): ?>
			<script src="<?=$plugin?>"></script>
		<?php endforeach; ?>
  		<script type="text/javascript">
			$(function() {
				//TinyMCE initialization
				$('textarea#description').tinymce({
					branding: false,
					plugins: 'link'
				});

				//Prepare form for submission
				$('form').submit(function(event) {
					tinymce.triggerSave();
				});
			});
		</script>
		<style type="text/css">
			div[aria-label="Insert link"] div.mce-container.mce-abs-layout-item.mce-first.mce-formitem label.mce-widget.mce-label.mce-abs-layout-item.mce-first {
				text-transform: uppercase;
				/* capitalize "URL" in tinymce link dialog */
			}
		</style>

		<!-- Style -->
		<link rel="stylesheet" type="text/css" href="/<?=$document_root?>includes/text.css">
		<link rel="stylesheet" type="text/css" href="/<?=$document_root?>includes/header.css">
		<link rel="stylesheet" type="text/css" href="/<?=$document_root?>includes/footer.css">
	</head>
	<body>
		<form action="update.php" method="POST">
			<input type="hidden" name="original_id" value="TEST">
			<textarea name="description" id="description">edit me</textarea>
			<input type="submit" value="Save changes">
		</form>
	</body>
</html>
