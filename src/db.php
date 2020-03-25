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
	private mysqli_stmt $getAdoptablePets;
	private mysqli_stmt $getAdoptablePetsBySpeciesPlural;
	private mysqli_stmt $getAllPets;

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
		if (!($this->getAdoptablePets = $this->db->prepare("
			SELECT * FROM (
			    SELECT pets.* FROM pets 
					LEFT JOIN statuses ON 
						pets.status = statuses.id AND 
						statuses.isAdoptable = 1
			) pet
			LEFT JOIN assets pic ON pet.photo = pic.id
			LEFT JOIN assets dsc ON pet.description = dsc.id
			"))) {
			log_err("Failed to prepare getAdoptablePets");
		}
		if (!($this->getAdoptablePetsBySpeciesPlural = $this->db->prepare("
			SELECT * FROM (
			    SELECT pets.* FROM pets 
					LEFT JOIN statuses ON 
						pets.status = statuses.id AND 
						statuses.listed = 1
			) pet
			LEFT JOIN assets pic ON pet.photo = pic.id
			LEFT JOIN assets dsc ON pet.description = dsc.id
			LEFT JOIN species ON 
			    pet.species = species.id AND 
			    species.plural = ?
			"))) {
			log_err("Failed to prepare getAdoptablePetsBySpeciesPlural");
		}
		if (!($this->getAllPets = $this->db->prepare("
			SELECT * FROM (
			    SELECT pets.* FROM pets 
					LEFT JOIN statuses ON 
						pets.status = statuses.id AND 
						statuses.deleted = 0
			) pet
			LEFT JOIN assets pic ON pet.photo = pic.id
			LEFT JOIN assets dsc ON pet.description = dsc.id
			"))) {
			log_err("Failed to prepare getAllPets");
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

	private static function createPet(array $pet, array $photos = []): Pet {
		$p              = new Pet();
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
		$p->photos      = array_map("self::createAsset", $photos);
		$p->status      = _G_statuses()[$pet["status"]];
		return $p;
	}

	public function getAssetByKey(string $key): Asset {
		if (!$this->getAssetByKey->bind_param("s", $key)) {
			log_err("Binding key $key to getAssetByKey failed");
			return null;
		}
		if (!$this->getAssetByKey->execute()) {
			log_err("Executing getAssetByKey failed");
			return null;
		}
		return self::createAsset($this->getAssetByKey->get_result()->fetch_assoc());
	}

	public function getAssetByPath(string $path): Asset {
		if (!$this->getAssetByPath->bind_param("s", $path)) {
			log_err("Binding path $path to getAssetByPath failed");
			return null;
		}
		if (!$this->getAssetByPath->execute()) {
			log_err("Executing getAssetByPath failed");
			return null;
		}
		return self::createAsset($this->getAssetByPath->get_result()->fetch_assoc());
	}

	public function getPetById(string $id): Pet {
		if (!$this->getPet->bind_param("s", $id)) {
			log_err("Binding id $id to getPet failed");
			return null;
		}
		if (!$this->getPet->execute()) {
			log_err("Executing getPet failed");
			return null;
		}

		if (!$this->getPhotos->bind_param("s", $id)) {
			log_err("Binding pet id $id to getPhotos failed");
		}
		if (!$this->getPhotos->execute()) {
			log_err("Executing getPhotos failed");
		}

		return self::createPet(
			$this->getPet->get_result()->fetch_assoc(),
			$this->getPhotos->get_result()->fetch_all(MYSQLI_ASSOC)
		);
	}

	public function getPetByPath(string $path): Pet {
		// TODO
		log_err("getPetByPath not yet implemented");
		return null;
	}

	// Returns an array of Pets
	public function getAdoptablePets(): array {
		// TODO
		log_err("getAdoptablePets not yet implemented");
		return [];
	}

	public function query(string $query): array {
		if (!($stmt = $this->db->prepare($query))) {
			log_err("Failed to prepare query $query");
			return [];
		}
		if (!($stmt->execute())) {
			log_err("Failed to execute query $query");
			return [];
		}
		return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
	}
}