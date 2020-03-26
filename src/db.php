<?php
require_once("common.php");
require_once("$secrets/config.php");
require_once("pet.php");
require_once("assets.php");

class Database {
	private mysqli $db;
	private mysqli_stmt $getAssetByKey;
	private mysqli_stmt $getAssetByPath;
	private mysqli_stmt $getAssetByAlternatePath;
	private mysqli_stmt $getPet;
	private mysqli_stmt $getPetByPath;
	private mysqli_stmt $getPetByLegacyPath;
	private mysqli_stmt $getPhotos;
	private mysqli_stmt $getAdoptablePets;
	private mysqli_stmt $getAdoptablePetsBySpeciesPlural;
	private mysqli_stmt $getAllPets;
	private mysqli_stmt $getAllSpecies;

	public function __construct() {
		$this->db = new mysqli(Config::$db_host, Config::$db_username, Config::$db_pass, Config::$db_name);

		if (!($this->getAssetByKey = $this->db->prepare("SELECT * FROM assets WHERE id = ?"))) {
			log_err("Failed to prepare getAssetByKey");
		}
		if (!($this->getAssetByPath = $this->db->prepare("SELECT * FROM assets WHERE path = ? LIMIT 1"))) {
			log_err("Failed to prepare getAssetByPath");
		}

		// Some assets may have non-canonical pathnames, such as those shared by multiple pets or those belonging to
		// pets with a different legacy_path.
		if (!($this->getAssetByAlternatePath = $this->db->prepare("
			SELECT assets.* FROM (
			    SELECT * from photos
				LEFT JOIN assets ON photos.photo = assets.id
				LEFT JOIN pets ON photos.pet = pets.id
			) WHERE CONCAT(
			    pets.path,
			    '/',
			    SUBSTRING_INDEX(assets.path, '/', -1)
			) = ? OR CONCAT(
			    pets.legacy_path,
			    '/',
			    SUBSTRING_INDEX(assets.path, '/', -1)
			) = ? 
			LIMIT 1
			"))) {
			log_err("Failed to prepare getAssetByAlternatePath");
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
		if (!($this->getPetByPath = $this->db->prepare("
			SELECT * FROM (
			    SELECT * FROM pets WHERE path = ? LIMIT 1
			) pet
			LEFT JOIN assets pic ON pet.photo = pic.id
			LEFT JOIN assets dsc ON pet.description = dsc.id
			"))) {
			log_err("Failed to prepare getPetByPath");
		}
		if (!($this->getPetByLegacyPath = $this->db->prepare("
			SELECT * FROM (
			    SELECT * FROM pets WHERE legacy_path = ? LIMIT 1
			) pet
			LEFT JOIN assets pic ON pet.photo = pic.id
			LEFT JOIN assets dsc ON pet.description = dsc.id
			"))) {
			log_err("Failed to prepare getPetByLegacyPath");
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
		if (!($this->getAllSpecies = $this->db->prepare("
			SELECT species.*, COUNT(pets.id) AS species_count
			FROM species LEFT JOIN pets ON species.id = pets.species
			GROUP BY species.id
			"))) {
			log_err("Failed to prepare getAllSpecies");
		}
	}

	private static function createAsset(array $asset): Asset {
		$a       = new Asset();
		$a->key  = $asset["id"];
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
		$p->breed       = $pet["breed"];
		return $p;
	}

	private static function createSpecies(array $species): Species {
		$s = new Species();
		$s->setAll($species);
		return $s;
	}

	public function getAssetByKey(int $key): Asset {
		if (!$this->getAssetByKey->bind_param("i", $key)) {
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
		} else {
			if (!$this->getAssetByPath->execute()) {
				log_err("Executing getAssetByPath failed");
			} else {
				$result = $this->getAssetByPath->get_result();
			}
		}

		if (!isset($result) || $result->num_rows === 0) {
			// Try the alternate paths
			if (!$this->getAssetByAlternatePath->bind_param("ss", $path, $path)) {
				log_err("Binding path $path to getAssetByAlternatePath failed");
				return null;
			}
			if (!$this->getAssetByAlternatePath->execute()) {
				log_err("Executing getAssetByAlternatePath failed");
				return null;
			}
			$result = $this->getAssetByAlternatePath->get_result();
			if ($result->num_rows === 0) {
				log_err("Found no asset with path $path");
				return null;
			}
		}
		return self::createAsset($result->fetch_assoc());
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
		if (!$this->getPetByPath->bind_param("s", $path)) {
			log_err("Binding path $path to getPetByPath failed");
		} else {
			if (!$this->getPet->execute()) {
				log_err("Executing getPetByPath failed");
			} else {
				$result = $this->getPetByPath->get_result();
			}
		}

		if (!isset($result) || $result->num_rows === 0) {
			if (!$this->getPetByLegacyPath->bind_param("s", $path)) {
				log_err("Binding path $path to getPetByLegacyPath failed");
				return null;
			}
			if (!$this->getPetByLegacyPath->execute()) {
				log_err("Executing getPetByLegacyPath failed");
				return null;
			}
			$result = $this->getPetByLegacyPath->get_result();
			if ($result->num_rows === 0) {
				log_err("Found no pet with path $path");
				return null;
			}
		}

		$pet = self::createPet($result->fetch_assoc());

		if (!$this->getPhotos->bind_param("s", $pet["id"])) {
			log_err("Binding pet id {$pet["id"]} to getPhotos failed");
		}
		if (!$this->getPhotos->execute()) {
			log_err("Executing getPhotos failed");
		}

		$pet->photos = array_map("self::createAsset", $this->getPhotos->get_result()->fetch_all(MYSQLI_ASSOC));
		return $pet;
	}

	// Returns an array of Pets
	public function getAdoptablePets(): array {
		if (!$this->getAdoptablePets->execute()) {
			log_err("Executing getAdoptablePets failed");
			return [];
		}
		return array_map("self::createPet", $this->getAdoptablePets->get_result()->fetch_all(MYSQLI_ASSOC));
	}

	public function getAdoptablePetsBySpeciesPlural(string $species): array {
		// Note table collation is case-insensitive
		if (!$this->getAdoptablePetsBySpeciesPlural->bind_param("s", $species)) {
			log_err("Binding species $species to getAdoptablePetsBySpeciesPlural failed");
			return [];
		}
		if (!$this->getAdoptablePetsBySpeciesPlural->execute()) {
			log_err("Executing getAdoptablePetsBySpeciesPlural failed");
			return [];
		}
		return array_map("self::createPet", $this->getAdoptablePetsBySpeciesPlural->get_result()->fetch_all(MYSQLI_ASSOC));
	}

	public function getAllPets(): array {
		if (!$this->getAllPets->execute()) {
			log_err("Executing getAllPets failed");
			return [];
		}
		return array_map("self::createPet", $this->getAdoptablePets->get_result()->fetch_all(MYSQLI_ASSOC));
	}

	public function getAllSpecies(): array {
		if (!$this->getAllSpecies->execute()) {
			log_err("Executing getAllSpecies failed");
			return [];
		}
		return array_map("self::createSpecies", $this->getAllSpecies->get_result()->fetch_all(MYSQLI_ASSOC));
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
