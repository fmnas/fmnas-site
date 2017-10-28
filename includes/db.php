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
			$list .= '<option value="1">'.print_r($options, true).'</option>';
		}
		catch (PDOException $e) {
			$list .= '<option value="">'.$e->getMessage().'</option>';
		}
		return $list;
	}
