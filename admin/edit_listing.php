<!DOCTYPE html>
<html>
	<head>
		<title>Listing editor</title>
		<meta charset="UTF-8">

		<!-- Jquery -->
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

		<!-- TinyMCE -->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/4.7.1/tinymce.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/4.7.1/jquery.tinymce.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/4.7.1/plugins/lists/plugin.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/4.7.1/plugins/link/plugin.min.js"></script>
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

		
	</head>
	<body>
		<form action="update.php" method="POST">
			<input type="hidden" name="original_id" value="TEST">
			<textarea name="description" id="description">edit me</textarea>
			<input type="submit" value="Save changes">
		</form>
	</body>
</html>
