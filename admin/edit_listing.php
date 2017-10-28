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

		<!-- BlueImp uploader -->
		<script src="<?=$blueimp_path?>"></script>
		<script src="<?=$blueimp_process_path?>"></script>
		<script src="<?=$blueimp_ui_path?>"></script>
		<script src="<?=$blueimp_jquery_ui_path?>"></script>

		<!-- Listing table -->
		<script src="/<?=$document_root?>includes/email_links.js"></script>
		<script src="/<?=$document_root?>includes/listing_table.js"></script>

		<!-- Style -->
		<link rel="stylesheet" type="text/css" href="/<?=$document_root?>includes/text.css">
		<link rel="stylesheet" type="text/css" href="/<?=$document_root?>includes/header.css">
		<link rel="stylesheet" type="text/css" href="/<?=$document_root?>includes/footer.css">
		<link rel="stylesheet" type="text/css" href="/<?=$document_root?>includes/listing_table.css.php">
	</head>
	<body>
		<form action="update.php" method="POST">
			<section class="preview">
				<h2>Link preview</h2>
				<?php
					$pets = array($pet); //display single pet in listing table
					require("$BASE/includes/listing_table.php");
				?>
			</section>
			<section class="pet_data">
				<h2>Pet data</h2>
				<input type="hidden" name="petkey" value="<?=$pet['petkey']?>">
				<label for="petid">ID</label>
				<input type="text" id="petid" name="id" minlength="3" maxlength="6" value="<?=$pet['id']?>">
				<label for="name">Name</label>
				<input type="text" id="name" name="name" value="<?=$pet['id']?>">
				<label for="species">Species</label>
				<select id="species" name="species">
					<?=build_option_list('species', true)?>
				</select>
				<label for="sex">Sex</label>
				<select id="sex" name="sex">
					<?=build_option_list('sexes')?>
				</select>
			</section>
			<section class="photos">
				<h2>Photos</h2>
			</section>
			<section class="description">
				<h2>Description</h2>
				<textarea name="description" id="description">edit me</textarea>
			</section>
			<nav>
				<input type="submit" value="Save changes">
			</nav>
		</form>
	</body>
</html>
