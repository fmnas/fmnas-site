<?php
	require_once("$BASE/config/db.php"); //Get database details

	try {
		$pdo = new PDO("mysql:host=$db_server;dbname=$db_name", $db_user, $db_pass); //Connect to database
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); //use exceptions for error handling
	}
	catch (PDOException $e) {
		die('Database connection failed: '.$e->getMessage()); //Failed to connect to database
	}

	function return_single_pet($q,$selector) { //make sure there is exactly one row in the results
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
