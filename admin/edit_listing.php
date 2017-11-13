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

			documentRoot = "<?=$document_root?>"; //will use later
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
		<script src="<?=$blueimp_templates_path?>"></script>
		<script src="<?=$blueimp_load_image_path?>"></script>
		<script src="<?=$blueimp_canvas_to_blob_path?>"></script>
		<script src="<?=$blueimp_gallery_path?>"></script>
		<script src="<?=$blueimp_path?>"></script>
		<script src="<?=$blueimp_process_path?>"></script>
		<script src="<?=$blueimp_image_path?>"></script>
		<script src="<?=$blueimp_ui_path?>"></script>
		<script src="<?=$blueimp_jquery_ui_path?>"></script>
		<link rel="stylesheet" type="text/css" href="<?=$blueimp_css_path?>">
		<link rel="stylesheet" type="text/css" href="<?=$blueimp_css_ui_path?>">

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

		<link rel="stylesheet" href="//blueimp.github.io/Gallery/css/blueimp-gallery.min.css">
	</head>
<body>
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
		<!-- The file upload form used as target for the file upload widget -->
		<form id="fileupload" action="//jquery-file-upload.appspot.com/" method="POST" enctype="multipart/form-data">
			<input type="hidden" name="petkey" value="<?=$pet['petkey']?>">
		    <!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
		    <div class="fileupload-buttonbar">
		        <div class="fileupload-buttons">
		            <!-- The fileinput-button span is used to style the file input field as button -->
		            <span class="fileinput-button">
		                <span>Add files...</span>
		                <input type="file" name="files[]" multiple>
		            </span>
		            <button type="submit" class="start">Start upload</button>
		            <button type="reset" class="cancel">Cancel upload</button>
		            <button type="button" class="delete">Delete</button>
		            <input type="checkbox" class="toggle">
		            <!-- The global file processing state -->
		            <span class="fileupload-process"></span>
		        </div>
		        <!-- The global progress state -->
		        <div class="fileupload-progress fade" style="display:none">
		            <!-- The global progress bar -->
		            <div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100"></div>
		            <!-- The extended global progress state -->
		            <div class="progress-extended">&nbsp;</div>
		        </div>
		    </div>
		    <!-- The table listing the files available for upload/download -->
		    <table role="presentation"><tbody class="files"></tbody></table>
		</form>
		<!-- The blueimp Gallery widget -->
		<div id="blueimp-gallery" class="blueimp-gallery blueimp-gallery-controls" data-filter=":even">
		    <div class="slides"></div>
		    <h3 class="title"></h3>
		    <a class="prev">‹</a>
		    <a class="next">›</a>
		    <a class="close">×</a>
		    <a class="play-pause"></a>
		    <ol class="indicator"></ol>
		</div>
		<!-- The template to display files available for upload -->
		<script id="template-upload" type="text/x-tmpl">
		{% for (var i=0, file; file=o.files[i]; i++) { %}
		    <tr class="template-upload fade">
		        <td>
		            <span class="preview"></span>
		        </td>
		        <td>
		            <p class="name">{%=file.name%}</p>
		            <strong class="error"></strong>
		        </td>
		        <td>
		            <p class="size">Processing...</p>
		            <div class="progress"></div>
		        </td>
		        <td>
		            {% if (!i && !o.options.autoUpload) { %}
		                <button class="start" disabled>Start</button>
		            {% } %}
		            {% if (!i) { %}
		                <button class="cancel">Cancel</button>
		            {% } %}
		        </td>
		    </tr>
		{% } %}
		</script>
		<!-- The template to display files available for download -->
		<script id="template-download" type="text/x-tmpl">
		{% for (var i=0, file; file=o.files[i]; i++) { %}
		    <tr class="template-download fade">
		        <td>
		            <span class="preview">
		                {% if (file.thumbnailUrl) { %}
		                    <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" data-gallery><img src="{%=file.thumbnailUrl%}"></a>
		                {% } %}
		            </span>
		        </td>
		        <td>
		            <p class="name">
		                <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" {%=file.thumbnailUrl?'data-gallery':''%}>{%=file.name%}</a>
		            </p>
		            {% if (file.error) { %}
		                <div><span class="error">Error</span> {%=file.error%}</div>
		            {% } %}
		        </td>
		        <td>
		            <span class="size">{%=o.formatFileSize(file.size)%}</span>
		        </td>
		        <td>
		            <button class="delete" data-type="{%=file.deleteType%}" data-url="{%=file.deleteUrl%}"{% if (file.deleteWithCredentials) { %} data-xhr-fields='{"withCredentials":true}'{% } %}>Delete</button>
		            <input type="checkbox" name="delete" value="1" class="toggle">
		        </td>
		    </tr>
		{% } %}
		</script>
		</section>
		<section class="description">
			<h2>Description</h2>
			<textarea name="description" id="description">edit me</textarea>
		</section>
		<nav>
			<input type="submit" value="Save changes">
		</nav>
</html>
