<?php
	require_once("$BASE/config/db.php"); //Get database details

	try {
		$pdo = new PDO("mysql:host=$db_server;dbname=$db_name", $db_user, $db_pass); //Connect to database
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); //use exceptions for error handling
	}
	catch (PDOException $e) {
		die('Database connection failed: '.$e->getMessage()); //Failed to connect to database
	}

	function return_single_pet($q,$selector='') { //make sure there is exactly one row in the results
		if($q->rowCount() !== 1) {
			echo '<pre>'.$q->rowCount()." pets match $selector:\r\n";
			print_r($q->fetchAll(PDO::FETCH_ASSOC));
			die();
		}
		return $q->fetch(PDO::FETCH_ASSOC);
	}

	function retrieve_pet_from_concat($idname) {
		global $pdo;
		try {
			$q = $pdo->prepare('SELECT * from pets WHERE CONCAT(id, name) = :idname');
			$q->execute([':idname'=>$idname]);
			return return_single_pet($q,$idname);
		}
		catch (PDOException $e) {
			die("Retrieving pet $idname failed: ".$e->getMessage());
		}
	}

	function retrieve_pet_from_key($key) {
		global $pdo;
		try {
			$q = $pdo->prepare('SELECT * from pets WHERE petkey = :petkey');
			$q->execute([':petkey'=>$key]);
			return return_single_pet($q,$key);
		}
		catch (PDOException $e) {
			die("Retrieving pet $key failed: ".$e->getMessage());
		}
	}

	function new_pet() {
		global $pdo;
		try {
			$q = $pdo->prepare('INSERT INTO pets () VALUES ()');
			$q->execute();
			return retrieve_pet_from_key($pdo->lastInsertId()); //get key from inserted row and use that
		}
		catch (PDOException $e) {
			die("Retrieving pet $key failed: ".$e->getMessage());
		}
	}

	try {
		$statuses = $pdo->query('SELECT * FROM status')->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
		$species  = $pdo->query('SELECT * FROM species')->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
		$sexes = $pdo->query('SELECT id, displaytext FROM sexes')->fetchAll(PDO::FETCH_KEY_PAIR);
	}
	catch (PDOException $e) {
		die("Retrieving tables failed: ".$e->getMessage());
	}

	function retrieve_adoptable_pets($species = NULL) {
		global $pdo;
		try {
			$sql='SELECT pets.* FROM pets INNER JOIN status ON pets.status = status.id WHERE status.hidelisting = 0';
			if($species) $sql.=' AND pets.species = :species';
			$q = $pdo->prepare($sql);
			$q->execute([':species'=>$species]);
			return $q->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
		}
		catch (PDOException $e) {
			die("Retrieving adoptable pets failed: ".$e->getMessage());
		}
	}

	function retrieve_adopted_pets() { return null; }

	function build_option_list($table, $selected = null, $allow_table_update = false) {
		global $pdo;
		$list .= '<option value="" '.(!$selected?' selected':'').'></option>';
		try {
			$options = $pdo->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
			foreach($options as $value=>$option){
				$list.= "<option value=\"$value\"";
				$list.= ($selected==$value?' selected':''); //add "selected" attribute to option matching $selected
				foreach($option as $column=>$value){ //data- attributes for each column
					$list.= " data-$column=\"";
					$list.= htmlspecialchars($value);
					$list.= "\"";
				}
				$list.= '>'; //end opening tag
				$list.= htmlspecialchars(array_values($option)[0]); //Display option as first non-key column
				$list.= "</option>";
			}
		}
		catch (PDOException $e) {
			$list .= '<option value="">'.$e->getMessage().'</option>';
		}
		if ($allow_table_update) {
			$list .= '<option value="-1" data-table="'.$table.'">Add/edit '.$table.'</option>';
		}
		return $list;
	}

	function get_description($petkey) {
		if(file_exists("$BASE/content/$petkey.html")) {
			return file_get_contents("$BASE/content/$petkey.html");
		}
		else {
			return file_get_contents("$BASE/templates/description.html");
		}
	}

	function save_description($petkey, $description) {
		$sanitized_description = str_replace('<?','&lt;?',$description); //sanitize PHP open tags
		$sanitized_description = str_replace('?>','&gt;?',$description);
		//TODO: sanitize script, etc with htmlpurifier
		return file_put_contents("$BASE/content/$petkey.html", $description);
	}

	function get_images($petkey) {
		//Returns a multidimensional array of all images associated with $petkey
		//Columns: id, pet, ordering, datetaken, filename, editedfrom, hidden
		global $pdo;
		try {
			$q = $pdo->prepare('SELECT * from images WHERE pet = :petkey');
			$q->execute([':petkey'=>petkey]);
			return $q->fetchAll(PDO::FETCH_ASSOC);
		}
		catch (PDOException $e) {
			die("Retrieving pet $key failed: ".$e->getMessage());
		}
	}

	function add_image($petkey, $image, $filename = NULL, $datetaken = NULL, $ordering = 1) {
		//Adds the image in $image (a gd resource) to the database as an image of pet $petkey
		//Optional parameters: filename $filename (default petkey_imagekey.jpg), DateTime $datetaken, ordering $ordering (default 1)
		//Returns the image key
		//TODO
		return;
	}

	function hide_image($imagekey, $hidden = TRUE) {
		//TODO
		return;
	}

	function reorder_image($imagekey, $ordering = 1) {
		//TODO
		return;
	}

	function add_edited_image($originalimagekey, $image) {
		//"Updates" the image in the database by creating a new image from $image with the properties of image $originalimagekey then hiding image $originalimagekey
		//Returns the new image key
		//TODO
		return;
	}

	function get_image($petid, $filename) {
		//Get a non-hidden image with filename $filename associated with pet with ID $petid (for use with "friendly" URLs)
		//Returns the image key
		//TODO
		return;
	}

	function urldoubleencode($path) {
		return urlencode(urlencode($path));
	}
