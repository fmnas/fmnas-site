<?php
	require_once($_SERVER['DOCUMENT_ROOT'].'/includes/paths.php');
	require_once("$BASE/includes/auth.php");
	require_once("$BASE/includes/db.php");

	//Return a JSON object with information on the newly-uploaded image.

	header('Content-Type: application/json;charset=utf-8');



	$phpFileUploadErrors = array(
	    0 => 'There is no error, the file uploaded with success',
	    1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
	    2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
	    3 => 'The uploaded file was only partially uploaded',
	    4 => 'No file was uploaded',
	    6 => 'Missing a temporary folder',
	    7 => 'Failed to write file to disk.',
	    8 => 'A PHP extension stopped the file upload.',
	);

	$response = array('error'=>NULL, 'filename'=>NULL, 'id'=>NULL, 'pet'=>NULL);

	$response['id'] = print_r($_FILES,true); //for debugging

	if(!($petkey = $_POST["petkey"])) { //Make sure a pet key was POSTed
		$response['error'] = 'No pet specified';
	}

	if($_FILES['files']['error'][0] !== 0) { //Make sure the file upload worked
		$response['error'] = $phpFileUploadErrors[$_FILES['files']['error'][0]];
	}

	$tempfile = $_FILES['files']['tmp_name'][0];

	if(!is_uploaded_file($tempfile)) { //security
		$response['error'] = "$tempfile is not an uploaded file";
	}

	if($response['error']) { //Die on all previous errors
		echo json_encode($response);
		exit;
	}

	//Check MIME type of uploaded file and attempt to create GD image
	$finfo = finfo_open(FILEINFO_MIME_TYPE);
	$mime = finfo_file($finfo,$tempfile);
	finfo_close($finfo);

	if($mime === 'image/png' && !@$im = imagecreatefrompng($tempfile)) {
		//Could not create an image from PNG
		$response['error'] = "Error loading PNG";
	}
	if($mime === 'image/gif' && !@$im = imagecreatefromgif($tempfile)) {
		$response['error'] = "Error loading GIF";
	}
	if(($mime === 'image/jpeg' || $mime === 'image/jpg') && !@$im = imagecreatefromjpeg($tempfile)) {
		$response['error'] = "Error loading JPEG";
	}
	if(!$response['error'] && !isset($im)) { //it wasn't any of those
		$response['error'] = "$mime is not a supported image format";
	}
	if($response['error']) { //Die on all previous errors
		echo json_encode($response);
		exit;
	}

	$filename = preg_replace('/[^A-Za-z0-9_.\-]/', '_', $filename); //replace special characters

	imagejpeg($im, "$BASE/content/$filename", 100); //temporary shim

	$response['filename'] = $filename;

	$response['pet'] = $_POST['petkey'];

	echo json_encode($response);
	exit;
