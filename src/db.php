<?php
require_once("common.php");
require_once("$secrets/config.php");
require_once("pet.php");
require_once("assets.php");

class Database {
	private mysqli $db;
	private mysqli_stmt $getAssetByKey;
	private mysqli_stmt $getAssetByPath;
	private mysqli_stmt $getPet;
	private mysqli_stmt $getPhotos;

	public function __construct() {
		$this->db = new mysqli(Config::$db_host, Config::$db_username, Config::$db_pass, Config::$db_name);

		if (!($this->getAssetByKey = $this->db->prepare("SELECT * FROM assets WHERE id = ?"))) {
			log_err("Failed to prepare getAssetByKey");
		}
		if (!($this->getAssetByPath = $this->db->prepare("SELECT * FROM assets WHERE path = ? LIMIT 1"))) {
			log_err("Failed to prepare getAssetByPath");
		}

		if (!($this->getPet = $this->db->prepare("
			SELECT * FROM ( 
			    SELECT * FROM pets WHERE id = ? 
			) pet 
    		LEFT JOIN assets pic ON pet.photo = pic.id 
			LEFT JOIN assets dsc ON pet.description = dsc.id
			"))) {
			log_err("Failed to prepare getPet");
		}
		if (!($this->getPhotos = $this->db->prepare("
			SELECT assets.* FROM (
				SELECT photos.assetId FROM (
					  SELECT * FROM pets WHERE id = ?
				) pet LEFT JOIN photos ON pet.id = photos.petId
			) p LEFT JOIN assets ON p.assetId = assets.id
			"))) {
			log_err("Failed to prepare getPhotos");
		}
	}

	private static function createAsset(array $asset): Asset {
		$a       = new Asset();
		$a->key  = $asset["key"];
		$a->path = $asset["path"];
		$a->setType($asset["type"]);
		$a->data = unserialize($asset["data"]);
		return $a;
	}

	public function getAssetByKey(string $key): Asset {
		if (!$this->getAssetByKey->bind_param("s", $key)) {
			log_err("Binding key $key to getAssetByKey failed");
			return new Asset();
		}
		if (!$this->getAssetByKey->execute()) {
			log_err("Executing getAssetByKey failed");
			return new Asset();
		}
		return self::createAsset($this->getAssetByKey->get_result()->fetch_assoc());
	}

	public function getAssetByPath(string $path): Asset {
		if (!$this->getAssetByPath->bind_param("s", $path)) {
			log_err("Binding path $path to getAssetByPath failed");
			return new Asset();
		}
		if (!$this->getAssetByPath->execute()) {
			log_err("Executing getAssetByPath failed");
			return new Asset();
		}
		return self::createAsset($this->getAssetByPath->get_result()->fetch_assoc());
	}

	public function getPet(string $id): Pet {
		$p = new Pet();

		if (!$this->getPet->bind_param("s", $id)) {
			log_err("Binding id $id to getPet failed");
			return $p;
		}
		if (!$this->getPet->execute()) {
			log_err("Executing getPet failed");
			return $p;
		}

		if (!$this->getPhotos->bind_param("s", $id)) {
			log_err("Binding pet id $id to getPhotos failed");
		}
		if (!$this->getPhotos->execute()) {
			log_err("Executing getPhotos failed");
		}

		$pet            = $this->getPet->get_result()->fetch_assoc();
		$p->id          = $pet["pet.id"];
		$p->name        = $pet["name"];
		$p->species     = _G_species()[$pet["species"]];
		$p->sex         = _G_sexes()[$pet["sex"]];
		$p->fee         = $pet["fee"];
		$p->photo       = self::createAsset([
			"id"   => $pet["pic.id"],
			"path" => $pet["pic.path"],
			"type" => $pet["pic.type"]
		]);
		$p->description = self::createAsset([
			"id"   => $pet["dsc.id"],
			"path" => $pet["dsc.path"],
			"type" => $pet["dsc.type"]
		]);
		$p->photos      = array_map("self::createAsset", $this->getPhotos->get_result()->fetch_all(MYSQLI_ASSOC));

		return $p;
	}
}