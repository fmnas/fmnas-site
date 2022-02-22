<?php
require_once "common.php";
require_once "pet.php";
require_once "assets.php";

class Database {
	protected mysqli $db;
	private mysqli_stmt $getAssetByKey;
	private mysqli_stmt $getAssetByPath;
	private mysqli_stmt $getAssetByAlternatePath;
	private mysqli_stmt $getPet;
	private mysqli_stmt $getPetById;
	private mysqli_stmt $getPetByPath;
	private mysqli_stmt $getPhotos;
	private mysqli_stmt $getAdoptablePets;
	private mysqli_stmt $getAdoptablePetsBySpecies;
	private mysqli_stmt $getAllPets;
	private mysqli_stmt $getAllSpecies;
	private mysqli_stmt $getAllIds;

	public function __construct() {
		$this->db = new mysqli(Config::$db_host, Config::$db_username, Config::$db_pass, Config::$db_name);
		$this->db->set_charset("utf8mb4");

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
				INNER JOIN species ON pets.species = species.id
				WHERE CONCAT_WS('/', species.plural, pets.path, SUBSTRING_INDEX(assets.path, '/', -1)) = ? OR
					  CONCAT_WS('/', species.plural, pets.legacy_path, SUBSTRING_INDEX(assets.path, '/', -1)) = ?
				LIMIT 1
			"))) {
			log_err("Failed to prepare getAssetByAlternatePath: {$this->db->error}");
		} else {
			$this->getAssetByAlternatePath = $getAssetByAlternatePath;
		}

		if (!($getPet = $this->db->prepare("
			SELECT * FROM listings WHERE id = ? and species = ? ORDER BY `order`, modified DESC
			"))) {
			log_err("Failed to prepare getPet: {$this->db->error}");
		} else {
			$this->getPet = $getPet;
		}

		if (!($getPetById = $this->db->prepare("
			SELECT * FROM listings WHERE id = ? ORDER BY `order`, modified DESC
			"))) {
			log_err("Failed to prepare getPetById: {$this->db->error}");
		} else {
			$this->getPetById = $getPetById;
		}

		if (!($getPhotos = $this->db->prepare("
			SELECT assets.* FROM photos JOIN assets ON photos.pet = ? AND photos.photo = assets.id 
			ORDER BY photos.order, assets.id
			"))) {
			log_err("Failed to prepare getPhotos: {$this->db->error}");
		} else {
			$this->getPhotos = $getPhotos;
		}

		if (!($getPetByPath = $this->db->prepare("
			SELECT * FROM listings WHERE (listing_path = ? OR legacy_path = ?) AND species = ?
			LIMIT 1
			"))) {
			log_err("Failed to prepare getPetByPath: {$this->db->error}");
		} else {
			$this->getPetByPath = $getPetByPath;
		}

		if (!($getAdoptablePets = $this->db->prepare("
			SELECT listings.* FROM listings JOIN statuses ON listings.status = statuses.id AND statuses.listed = 1
      ORDER BY `order`, modified DESC
			"))) {
			log_err("Failed to prepare getAdoptablePets: {$this->db->error}");
		} else {
			$this->getAdoptablePets = $getAdoptablePets;
		}

		if (!($getAdoptablePetsBySpecies = $this->db->prepare("
			SELECT listings.* FROM listings JOIN statuses ON listings.status = statuses.id AND statuses.listed = 1 
			WHERE listings.species = ? ORDER BY `order`, modified DESC
			"))) {
			log_err("Failed to prepare getAdoptablePetsBySpecies: {$this->db->error}");
		} else {
			$this->getAdoptablePetsBySpecies = $getAdoptablePetsBySpecies;
		}

		if (!($getAllPets = $this->db->prepare("
			SELECT * FROM listings ORDER BY `order`, modified DESC
			"))) {
			log_err("Failed to prepare getAllPets: {$this->db->error}");
		} else {
			$this->getAllPets = $getAllPets;
		}

		if (!($getAllSpecies = $this->db->prepare("
			SELECT species.*, COUNT(pets.id) AS species_count
			FROM species CROSS JOIN statuses
			LEFT JOIN pets ON species.id = pets.species AND pets.status = statuses.id
			WHERE listed = 1 GROUP BY species.id ORDER BY species.id
			"))) {
			log_err("Failed to prepare getAllSpecies: {$this->db->error}");
		} else {
			$this->getAllSpecies = $getAllSpecies;
		}

		if (!($getAllIds = $this->db->prepare("
			SELECT id FROM pets
		"))) {
			log_err("Failed to prepare getAllIds: {$this->db->error}");
		} else {
			$this->getAllIds = $getAllIds;
		}
	}

	private static function createSpecies(array $species): Species {
		$s = new Species();
		$s->setAll($species);
		return $s;
	}

	public function getAssetByKey(int $key): ?Asset {
		if (!$this->getAssetByKey->bind_param("i", $key)) {
			log_err("Binding key $key to getAssetByKey failed: {$this->db->error}");
			return null;
		}
		if (!$this->getAssetByKey->execute()) {
			log_err("Executing getAssetByKey failed: {$this->db->error}");
			return null;
		}
		$result = $this->getAssetByKey->get_result();
		if ($result === false || $result->num_rows === 0) {
			return null;
		}
		return self::createAsset($result->fetch_assoc());
	}

	private static function createAsset(array $asset): ?Asset {
		if (!$asset["id"]) {
			return null;
		}
		$a = new Asset();
		$a->key = $asset["id"];
		$a->path = $asset["path"];
		$a->setType($asset["type"]);
		$a->data = ($asset["data"] ? unserialize($asset["data"]) : []);
		return $a;
	}

	public function getAssetByPath(string $path): ?Asset {
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
				return urldecode($path) !== $path ? $this->getAssetByPath(urldecode($path)) : null;
			}
		}

		return self::createAsset($result->fetch_assoc());
	}

	public function getPetById(string $id, ?Species $species = null): ?Pet {
		$r = null;
		if ($species === null) {
			if (!$this->getPetById->bind_param("s", $id)) {
				log_err("Binding id $id to getPetById failed: {$this->db->error}");
				return null;
			}
			if (!$this->getPetById->execute()) {
				log_err("Executing getPetById failed: {$this->db->error}");
				return null;
			}
			$r = $this->getPetById->get_result()->fetch_assoc();
		} else {
			if (!$this->getPet->bind_param("ss", $id, $species->id)) {
				log_err("Binding id $id and species {$species->id} to getPet failed: {$this->db->error}");
				return null;
			}
			if (!$this->getPet->execute()) {
				log_err("Executing getPet failed: {$this->db->error}");
				return null;
			}
			$r = $this->getPet->get_result()->fetch_assoc();
		}
		if ($r === null) {
			return null;
		}

		if (!$this->getPhotos->bind_param("s", $id)) {
			log_err("Binding pet id $id to getPhotos failed: {$this->db->error}");
		}
		if (!$this->getPhotos->execute()) {
			log_err("Executing getPhotos failed: {$this->db->error}");
		}

		return self::createPet(
				$r,
				$this->getPhotos->get_result()->fetch_all(MYSQLI_ASSOC)
		);
	}

	private static function createPet(array $pet, array $photos = []): ?Pet {
		if (!$pet["id"] || !$pet["name"] || !$pet["species"] || !$pet["listing_path"]) {
			return null;
		}
		$p = new Pet();
		$p->id = $pet["id"];
		$p->name = $pet["name"];
		$p->species = _G_species()[$pet["species"]];
		$p->path = $pet["listing_path"];
		$p->sex = $pet["sex"] ? _G_sexes()[$pet["sex"]] : null;
		$p->fee = $pet["fee"];
		$p->photo = self::createAsset([
				"id" => $pet["pic_id"],
				"data" => $pet["pic_data"],
				"path" => $pet["pic_path"],
				"type" => $pet["pic_type"],
		]);
		$p->description = self::createAsset([
				"id" => $pet["dsc_id"],
				"data" => $pet["dsc_data"],
				"path" => $pet["dsc_path"],
				"type" => $pet["dsc_type"],
		]);
		$p->photos = array_map("self::createAsset", $photos);
		$p->status = _G_statuses()[$pet["status"]];
		$p->breed = $pet["breed"];
		$p->dob = $pet["dob"];
		$p->bonded = $pet["bonded"];
		$p->friend = null;
		if ($p->bonded === 1) {
			$p->friend = self::createPet([
					"id" => $pet["friend"],
					"name" => $pet["friend_name"],
					"sex" => $pet["friend_sex"],
					"pic_id" => $pet["friend_pic_id"],
					"pic_data" => $pet["friend_pic_data"],
					"pic_path" => $pet["friend_pic_path"],
					"pic_type" => $pet["friend_pic_type"],
					"breed" => $pet["friend_breed"],
					"dob" => $pet["friend_dob"],
					"bonded" => 2,
					"species" => $pet["species"],
					"fee" => $pet["fee"],
					"dsc_data" => null,
					"dsc_id" => null,
					"dsc_path" => null,
					"dsc_type" => null,
					"listing_path" => $pet["listing_path"],
					"status" => $pet["status"],
					"order" => $pet["order"],
					"adoption_date" => $pet["adoption_date"],
					"modified" => $pet["modified"],
			]);
		}
		$p->order = $pet["order"];
		$p->adoption_date = $pet["adoption_date"];
		$p->modified = $pet["modified"];
		return $p;
	}

	public function getPetByPath(string $path): ?Pet {
		if (urldecode($path) !== $path) {
			return $this->getPetByPath(urldecode($path));
		}

		$expected_species = null;

		foreach (_G_species() as $species) {
			/* @var $species Species */
			$prefix = $species->plural() . "/";
			if (startsWith(strtolower($path), strtolower($prefix))) {
				$path = substr($path, strlen($prefix));
				$expected_species = $species;
				break;
			}
		}

		if ($expected_species === null) {
			log_err("Found no species in path $path");
			return null;
		}

		$species_id = $expected_species->id;

		if (!$this->getPetByPath->bind_param("sss", $path, $path, $species_id)) {
			log_err("Binding path $path to getPetByPath failed: {$this->db->error}");
		} else {
			if (!$this->getPetByPath->execute()) {
				log_err("Executing getPetByPath failed: {$this->db->error}");
			} else {
				$result = $this->getPetByPath->get_result();
			}
		}

		if (!isset($result) || $result->num_rows === 0) {
			log_err("Found no pet with path $path");
			return null;
		}

		$pet_arr = $result->fetch_assoc();
		if (!$this->getPhotos->bind_param("s", $pet_arr["id"])) {
			log_err("Binding pet id {$pet_arr["id"]} to getPhotos failed");
		}
		if (!$this->getPhotos->execute()) {
			log_err("Executing getPhotos failed");
		}

		return self::createPet($pet_arr, $this->getPhotos->get_result()->fetch_all(MYSQLI_ASSOC));
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
		$id = $species->id;
		if (!$this->getAdoptablePetsBySpecies->bind_param("i", $id)) {
			log_err("Binding species id $id to getAdoptablePetsBySpecies failed");
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

	public function getAllIds(): ?array {
		if (!$this->getAllIds->execute()) {
			log_err("Executing getAllIds failed");
			return null;
		}
		return array_map("self::extractId", $this->getAllIds->get_result()->fetch_all(MYSQLI_ASSOC));
	}

	private static function extractId(array $row): string {
		return $row['id'] ?? '';
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
		$result = $stmt->get_result();
		if (!$result) {
			log_err("Didn't get any results for query $query");
			return [];
		}
		return $result->fetch_all(MYSQLI_ASSOC);
	}
}
