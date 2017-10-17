<?php
	require_once("$BASE/config/db.php"); //Get database details

	try {
		$pdo = new PDO("mysql:host=$db_server;dbname=$db_name", $db_user, $db_pass); //Connect to database
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); //use exceptions for error handling
	}
	catch (PDOException $e) {
		die('Database connection failed: '.$e->getMessage()); //Failed to connect to database
	}

	function retrieve_pet_from_concat($idname) {
		global $pdo;
		try {
			$q = $pdo->prepare('SELECT * from pets WHERE CONCAT(id, name) = :idname');
			$q->execute([':idname'=>$idname]);
			if($q->rowCount() !== 1) {
				echo '<pre>'.$q->rowCount()." pets match $idname:\r\n";
				print_r($q->fetchAll(PDO::FETCH_ASSOC));
				die();
			}
			return $q->fetch(PDO::FETCH_ASSOC);
		}
		catch (PDOException $e) {
			die('Retrieving pet failed: '.$e->getMessage());
		}
	}

	//BEGIN PREPARED STATEMENTS
	//Get pet from parameter in format IDName
	if(!($retrieve_pet_from_concat = $mysqli->prepare("SELECT * FROM pets WHERE CONCAT(id, name) = (?)")))
		die('retrieve_pet_from_concat: Prepare failed: ('.$mysqli->errno.')'.$mysqli->error);
