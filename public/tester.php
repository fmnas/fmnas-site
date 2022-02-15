<?php
require_once '../src/common.php';
require_once '../src/resize.php';
?>
<!DOCTYPE html>
<title>Tester</title>
<meta charset="utf-8">
<meta name="robots" content="noindex,nofollow">
<div class="results">
<?php
	$test = $_POST["test"] ?? "none";
	switch ($test) {
	case "image-size":
		echo "<p>";
		try {
			echo "Local image size is: " . implode('x', size($_FILES["file"]["tmp_name"]));
		} catch (ImageResizeException $e) {
			echo "<strong>Exception getting image size.</strong><pre>";
			var_dump($e);
			echo "</pre>";
		}
		echo "<p>";
		try {
			echo "<br>Remote image size is: " . implode('x', remoteSize($_FILES["file"]["tmp_name"]));
		} catch (ImageResizeException $e) {
			echo "<strong>Exception getting remote image size.</strong><pre>";
			var_dump($e);
			echo "</pre>";
		}
		break;
	case "resize-image":
		echo "<p>";
		try {
			$new = time() . "_1.jpg";
			$newAbs = cached_assets() . "/$new";
			$newLink = "/assets/cache/$new";
			resize($_FILES["file"]["tmp_name"], $newAbs, $_POST["height"]);
			echo "Locally resized: <a href=\"$newLink\">$new</a>";
		} catch (ImageResizeException $e) {
			echo "<strong>Exception resizing image.</strong><pre>";
			var_dump($e);
			echo "</pre>";
		}
		echo "<p>";
		try {
			$new = time() . "_2.jpg";
			$newAbs = cached_assets() . "/$new";
			$newLink = "/assets/cache/$new";
			remoteResize($_FILES["file"]["tmp_name"], $newAbs, $_POST["height"]);
			echo "Remotely resized: <a href=\"$newLink\">$new</a>";
		} catch (ImageResizeException $e) {
			echo "<strong>Exception remotely resizing image.</strong><pre>";
			var_dump($e);
			echo "</pre>";
		}
		break;
	case "none":
		break;
	default:
		echo "Test $test not implemented.";
		break;
	}
?>
</div>
<form method="POST" enctype="multipart/form-data">
	<select name="test">
		<option value="image-size">image-size</option>
		<option value="resize-image">resize-image</option>
	</select><br>
	file: <input type="file" name="file"><br>
	height: <input type="number" value="480" name="height"><br>
	<input type="submit">
</form>
