<!DOCTYPE html>
<?php
	require_once($_SERVER['DOCUMENT_ROOT'].'/includes/paths.php');
	require_once("$BASE/includes/auth.php");
	require_once("$BASE/includes/db.php");

	if($_GET["petkey"]) $pet = retrieve_pet_from_key($_GET["petkey"]);
	elseif($_GET["pet"]) $pet = retrieve_pet_from_concat($_GET["pet"]);
	else $pet = new_pet();
	var_dump($pet);
?>
<html>
	<head>
		<title>Please enable JavaScript</title>
		<script type="text/javascript">
			titlePart1 = "Listing editor for ";
			titlePart2 = "<?=($pet['name']?:'new pet')?>";
			titlePart3 = "<?=' - '.$shelter_name?>";
			function updateTitle(){
				$('title').text(titlePart1+titlePart2+titlePart3);
			}
			/* Will automatically run later once page loads due to firing of input event on Name field */
		</script>
		<meta charset="UTF-8">

		<!-- Jquery -->
		<script src="<?=$jquery_path?>"></script>

		<!-- Jquery UI -->
		<script src="<?=$jquery_ui_path?>"></script>
		<link rel="stylesheet" type="text/css" href="<?=$jquery_ui_css_path?>">

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
		<link rel="stylesheet" type="text/css" href="/<?=$document_root?>includes/listing_editor.css">

		<script src="/<?=$document_root?>includes/edit_listing.js"></script>
	</head>
	<body>
		<form action="/<?=$document_root?>admin/update_listing.php" method="POST">
			<section class="preview">
				<h2>Preview</h2>
				<p>(As shown on <span class="speciespagetitle"><?=$pet['species']?$species[$pet['species']]['pagetitle']:'listings'?></span> page)</p>
				<?php
					$pets = array($pet); //display single pet in listing table
					require("$BASE/includes/listing_table.php");
				?>
			</section>
			<section class="pet_data">
				<h2>Pet data</h2>
				<input type="hidden" name="petkey" value="<?=$pet['petkey']?>">
				<label for="petid">ID</label>
				<input type="text" id="petid" name="id" minlength="3" maxlength="32" value="<?=$pet['id']?>">
				<label for="name">Name</label>
				<input type="text" id="name" name="name" minlength="1" maxlength="255" value="<?=$pet['name']?>">
				<label for="species">Species</label>
				<select id="species" name="species">
					<?=build_option_list('species', $pet['species'], true)?>
				</select>
				<label for="sex">Sex</label>
				<select id="sex" name="sex">
					<?=build_option_list('sexes', $pet['sex'])?>
				</select>
				<label for="dob"><abbr title="Date of birth">DOB</abbr></label>
				<input type="text" id="dob" name="dob" value="<?=date('n/j/y',strtotime($pet['dob']))?>">
				<input type="hidden" id="dob-iso" name="dob-iso" value="<?=$pet['dob']?>">
				<label for="approx">Approximate <abbr title="Date of birth">DOB</abbr></label>
				<input type="checkbox" id="approx" name="approx" <?=$pet["estimate"]?'checked':''?>>
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
