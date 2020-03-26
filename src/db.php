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
	private mysqli_stmt $getAdoptablePetsBySpecies;
	private mysqli_stmt $getAllPets;
	private mysqli_stmt $getAllSpecies;

	public function __construct() {
		$this->db = new mysqli(Config::$db_host, Config::$db_username, Config::$db_pass, Config::$db_name);

		if (!($getAssetByKey = $this->db->prepare("SELECT * FROM assets WHERE id = ?"))) {
			log_err("Failed to prepare getAssetByKey: {$this->db->error}");
		} else {
			$this->getAssetByKey = $getAssetByKey;
		}
		if (@!($getAssetByPath = $this->db->prepare("SELECT * FROM assets WHERE path = ? LIMIT 1"))) {
			log_err("Failed to prepare getAssetByPath: {$this->db->error}");
		} else {
			$this->getAssetByPath = $getAssetByPath;
		}

		// Some assets may have non-canonical pathnames, such as those shared by multiple pets or those belonging to
		// pets with a different legacy_path.
		if (!($getAssetByAlternatePath = $this->db->prepare("
			SELECT assets.* FROM photos
				LEFT JOIN assets ON photos.photo = assets.id
				LEFT JOIN pets ON photos.pet = pets.id 
				INNER JOIN species ON pets.species = species.id AND (
					CONCAT_WS('/', species.plural, pets.path, SUBSTRING_INDEX(assets.path, '/', -1)) = ? OR
					CONCAT_WS('/', species.plural, pets.legacy_path, SUBSTRING_INDEX(assets.path, '/', -1)) = ?
				) LIMIT 1
			"))) {
			log_err("Failed to prepare getAssetByAlternatePath: {$this->db->error}");
		} else {
			$this->getAssetByAlternatePath = $getAssetByAlternatePath;
		}

		if (!($getPet = $this->db->prepare("
			SELECT * FROM ( 
			    SELECT * FROM pets WHERE id = ? 
			) pet 
    		LEFT JOIN assets pic ON pet.photo = pic.id 
			LEFT JOIN assets dsc ON pet.description = dsc.id
			"))) {
			log_err("Failed to prepare getPet: {$this->db->error}");
		} else {
			$this->getPet = $getPet;
		}
		if (!($getPhotos = $this->db->prepare("
			SELECT assets.* FROM (
				SELECT photos.photo FROM (
					  SELECT * FROM pets WHERE id = ?
				) pet LEFT JOIN photos ON pet.id = photos.pet
			) p LEFT JOIN assets ON p.photo = assets.id
			"))) {
			log_err("Failed to prepare getPhotos: {$this->db->error}");
		} else {
			$this->getPhotos = $getPhotos;
		}
		if (!($getPetByPath = $this->db->prepare("
			SELECT 
			       pets.*, 
			       pic.id AS pic_id, 
			       pic.data AS pic_data, 
			       pic.type AS pic_type,
			       pic.path AS pic_path,
			       dsc.id AS dsc_id,
			       dsc.data AS dsc_data,
			       dsc.type AS dsc_type,
			       dsc.path AS dsc_path
			FROM (
			    SELECT * FROM pets WHERE path = ? LIMIT 1
			) pet
			LEFT JOIN assets pic ON pet.photo = pic.id
			LEFT JOIN assets dsc ON pet.description = dsc.id
			"))) {
			log_err("Failed to prepare getPetByPath: {$this->db->error}");
		} else {
			$this->getPetByPath = $getPetByPath;
		}
		if (!($getPetByLegacyPath = $this->db->prepare("
			SELECT * FROM (
			    SELECT * FROM pets WHERE legacy_path = ? LIMIT 1
			) pet
			LEFT JOIN assets pic ON pet.photo = pic.id
			LEFT JOIN assets dsc ON pet.description = dsc.id
			"))) {
			log_err("Failed to prepare getPetByLegacyPath: {$this->db->error}");
		} else {
			$this->getPetByLegacyPath = $getPetByLegacyPath;
		}
		if (!($getAdoptablePets = $this->db->prepare("
			SELECT 
			       pets.*, 
			       pic.id AS pic_id, 
			       pic.data AS pic_data, 
			       pic.type AS pic_type,
			       pic.path AS pic_path,
			       dsc.id AS dsc_id,
			       dsc.data AS dsc_data,
			       dsc.type AS dsc_type,
			       dsc.path AS dsc_path
			FROM (
			    SELECT pets.* FROM pets 
					LEFT JOIN statuses ON 
						pets.status = statuses.id AND 
						statuses.listed = 1
			) pet
			LEFT JOIN assets pic ON pet.photo = pic.id
			LEFT JOIN assets dsc ON pet.description = dsc.id
			"))) {
			log_err("Failed to prepare getAdoptablePets: {$this->db->error}");
		} else {
			$this->getAdoptablePets = $getAdoptablePets;
		}
//		if (!($getAdoptablePetsBySpeciesPlural = $this->db->prepare("
//			SELECT * FROM (
//			    SELECT pets.* FROM pets
//					LEFT JOIN statuses ON
//						pets.status = statuses.id AND
//						statuses.listed = 1
//			) pet
//			LEFT JOIN assets pic ON pet.photo = pic.id
//			LEFT JOIN assets dsc ON pet.description = dsc.id
//			LEFT JOIN species ON
//			    pet.species = species.id AND
//			    species.plural = ?
//			"))) {
//			log_err("Failed to prepare getAdoptablePetsBySpeciesPlural: {$this->db->error}");
//		} else {
//			$this->getAdoptablePetsBySpeciesPlural = $getAdoptablePetsBySpeciesPlural;
//		}
		if (!($getAdoptablePetsBySpecies = $this->db->prepare("
			SELECT 
			       pets.*, 
			       pic.id AS pic_id, 
			       pic.data AS pic_data, 
			       pic.type AS pic_type,
			       pic.path AS pic_path,
			       dsc.id AS dsc_id,
			       dsc.data AS dsc_data,
			       dsc.type AS dsc_type,
			       dsc.path AS dsc_path
			FROM pets
			LEFT JOIN statuses ON
			    pets.species = ? AND
				pets.status = statuses.id AND 
				statuses.listed = 1
			LEFT JOIN assets pic ON pets.photo = pic.id
			LEFT JOIN assets dsc ON pets.description = dsc.id
			"))) {
			log_err("Failed to prepare getAdoptablePetsBySpecies: {$this->db->error}");
		} else {
			$this->getAdoptablePetsBySpecies = $getAdoptablePetsBySpecies;
		}
		if (!($getAllPets = $this->db->prepare("
			SELECT 
			       pets.*, 
			       pic.id AS pic_id, 
			       pic.data AS pic_data, 
			       pic.type AS pic_type,
			       pic.path AS pic_path,
			       dsc.id AS dsc_id,
			       dsc.data AS dsc_data,
			       dsc.type AS dsc_type,
			       dsc.path AS dsc_path
			FROM (
			    SELECT pets.* FROM pets 
					LEFT JOIN statuses ON 
						pets.status = statuses.id AND 
						statuses.deleted = 0
			) pet
			LEFT JOIN assets pic ON pet.photo = pic.id
			LEFT JOIN assets dsc ON pet.description = dsc.id
			"))) {
			log_err("Failed to prepare getAllPets: {$this->db->error}");
		} else {
			$this->getAllPets = $getAllPets;
		}

		if (!($getAllSpecies = $this->db->prepare("
			SELECT species.*, COUNT(pets.id) AS species_count
			FROM species LEFT JOIN pets ON species.id = pets.species
			GROUP BY species.id
			"))) {
			log_err("Failed to prepare getAllSpecies: {$this->db->error}");
		} else {
			$this->getAllSpecies = $getAllSpecies;
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
		var_dump($pet);
		$p->id          = $pet["id"];
		$p->name        = $pet["name"];
		$p->species     = _G_species()[$pet["species"]];
		$p->sex         = _G_sexes()[$pet["sex"]];
		$p->fee         = $pet["fee"];
		$p->photo       = self::createAsset([
			"id"   => $pet["pic_id"],
			"data" => $pet["pic_data"],
			"path" => $pet["pic_path"],
			"type" => $pet["pic_type"]
		]);
		$p->description = self::createAsset([
			"id"   => $pet["dsc_id"],
			"data" => $pet["dsc_data"],
			"path" => $pet["dsc_path"],
			"type" => $pet["dsc_type"]
		]);
		$p->photos      = array_map("self::createAsset", $photos);
		$p->status      = _G_statuses()[$pet["status"]];
		$p->breed       = $pet["breed"];
		$p->dob         = $pet["dob"];
		return $p;
	}

	private static function createSpecies(array $species): Species {
		$s = new Species();
		$s->setAll($species);
		return $s;
	}

	public function getAssetByKey(int $key): Asset {
		if (!$this->getAssetByKey->bind_param("i", $key)) {
			log_err("Binding key $key to getAssetByKey failed: {$this->db->error}");
			return null;
		}
		if (!$this->getAssetByKey->execute()) {
			log_err("Executing getAssetByKey failed: {$this->db->error}");
			return null;
		}
		return self::createAsset($this->getAssetByKey->get_result()->fetch_assoc());
	}

	public function getAssetByPath(string $path): Asset {
		if (!$this->getAssetByPath->bind_param("s", $path)) {
			log_err("Binding path $path to getAssetByPath failed: {$this->db->error}");
		} else {
			if (!$this->getAssetByPath->execute()) {
				log_err("Executing getAssetByPath failed: {$this->db->error}");
			} else {
				$result = $this->getAssetByPath->get_result();
			}
		}

		if (!isset($result) || $result->num_rows === 0) {
			// Try the alternate paths
			if (!$this->getAssetByAlternatePath->bind_param("ss", $path, $path)) {
				log_err("Binding path $path to getAssetByAlternatePath failed: {$this->db->error}");
				return null;
			}
			if (!$this->getAssetByAlternatePath->execute()) {
				log_err("Executing getAssetByAlternatePath failed: {$this->db->error}");
				return null;
			}
			$result = $this->getAssetByAlternatePath->get_result();
			if ($result->num_rows === 0) {
				log_err("Found no asset with path $path: {$this->db->error}");
				return null;
			}
		}
		return self::createAsset($result->fetch_assoc());
	}

	public function getPetById(string $id): Pet {
		if (!$this->getPet->bind_param("s", $id)) {
			log_err("Binding id $id to getPet failed: {$this->db->error}");
			return null;
		}
		if (!$this->getPet->execute()) {
			log_err("Executing getPet failed: {$this->db->error}");
			return null;
		}

		if (!$this->getPhotos->bind_param("s", $id)) {
			log_err("Binding pet id $id to getPhotos failed: {$this->db->error}");
		}
		if (!$this->getPhotos->execute()) {
			log_err("Executing getPhotos failed: {$this->db->error}");
		}

		return self::createPet(
			$this->getPet->get_result()->fetch_assoc(),
			$this->getPhotos->get_result()->fetch_all(MYSQLI_ASSOC)
		);
	}

	public function getPetByPath(string $path): Pet {
		if (!$this->getPetByPath->bind_param("s", $path)) {
			log_err("Binding path $path to getPetByPath failed: {$this->db->error}");
		} else {
			if (!$this->getPet->execute()) {
				log_err("Executing getPetByPath failed: {$this->db->error}");
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

	public function getAdoptablePetsBySpecies(Species $species): array {
		// Note table collation is case-insensitive
		if (!$this->getAdoptablePetsBySpecies->bind_param("i", $species->__get("id"))) {
			log_err("Binding species id {$species->__get("id")} to getAdoptablePetsBySpecies failed");
			return [];
		}
		if (!$this->getAdoptablePetsBySpecies->execute()) {
			log_err("Executing getAdoptablePetsBySpecies failed");
			return [];
		}
		return array_map("self::createPet", $this->getAdoptablePetsBySpecies->get_result()->fetch_all(MYSQLI_ASSOC));
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
