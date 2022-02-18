<?php
require_once "../../src/common.php";
require_once "$src/db.php";

class DatabaseWriter extends Database {
	private mysqli_stmt $setConfigValue;
	private mysqli_stmt $insertAsset;
	private mysqli_stmt $updateAsset;
	private mysqli_stmt $insertPet;
	private mysqli_stmt $deletePet;
	private mysqli_stmt $deletePhotos;
	private mysqli_stmt $insertPhoto;
	private mysqli_stmt $clearConflictingAssets;
	private mysqli_stmt $setPair;

	public function __construct() {
		parent::__construct();
		if (!($setConfigValue = $this->db->prepare("
			UPDATE config SET config_value=? WHERE config_key=? LIMIT 1
			"))) {
			log_err("Failed to prepare setConfigValue: {$this->db->error}");
		} else {
			$this->setConfigValue = $setConfigValue;
		}

		if (!($updateAsset = $this->db->prepare("
			UPDATE assets SET path=?, data=?, type=? WHERE id=? LIMIT 1
			"))) {
			log_err("Failed to prepare updateAsset: {$this->db->error}");
		} else {
			$this->updateAsset = $updateAsset;
		}

		if (!($insertAsset = $this->db->prepare("
			INSERT INTO assets (id, path, data, type) VALUES(NULL, ?, ?, ?)
			"))) {
			log_err("Failed to prepare insertAsset: {$this->db->error}");
		} else {
			$this->insertAsset = $insertAsset;
		}

		if (!($deletePet = $this->db->prepare("
			DELETE FROM pets WHERE id=?
			"))) {
			log_err("Failed to prepare deletePet: {$this->db->error}");
		} else {
			$this->deletePet = $deletePet;
		}

		if (!($insertPet = $this->db->prepare("
			REPLACE INTO pets (id, name, species, breed, dob, sex, fee, photo, description, status, bonded, friend, adoption_date, `order`)
			VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
			"))) {
			log_err("Failed to prepare insertPet: {$this->db->error}");
		} else {
			$this->insertPet = $insertPet;
		}

		if (!($setPair = $this->db->prepare("
			UPDATE pets SET bonded=?, friend=? WHERE id=?
		"))) {
			log_err("Failed to prepare setPair: {$this->db->error}");
		} else {
			$this->setPair = $setPair;
		}

		if (!($deletePhotos = $this->db->prepare("
			DELETE FROM photos WHERE pet=?
			"))) {
			log_err("Failed to prepare deletePhotos: {$this->db->error}");
		} else {
			$this->deletePhotos = $deletePhotos;
		}

		if (!($insertPhoto = $this->db->prepare("
			INSERT INTO photos (pet, photo) VALUES(?, ?)
			"))) {
			log_err("Failed to prepare insertPhoto: {$this->db->error}");
		} else {
			$this->insertPhoto = $insertPhoto;
		}

		if (!($clearConflictingAssets = $this->db->prepare("
			UPDATE assets SET path=NULL where path=?
		"))) {
			log_err("Failed to prepare clearConflictingAssets: {$this->db->error}");
		} else {
			$this->clearConflictingAssets = $clearConflictingAssets;
		}
	}

	public function setConfigValue(string $key, string $value): ?string {
		global $src;

		$error = null;
		if (!$this->db->begin_transaction()) {
			$error = "Failed to begin transaction";
		} else if (!$this->setConfigValue->bind_param("ss", $value, $key)) {
			$error = "Binding $key,$value to setConfigValue failed: {$this->db->error}";
		} else if (!$this->setConfigValue->execute()) {
			$error = "Executing setConfigValue failed: {$this->db->error}";
		}
		if ($error) {
			log_err($error);
			$this->db->rollback();
			return $error;
		}
		return $this->db->commit() ? null : "Failed to commit";
	}

	private function insertAssetOneshot(array $value): string|int {
		$error = null;
		$path = ($value['path'] ?? null) ?: null;
		$data = isset($value['data']) ? serialize($value['data']) : null;
		$type = $value['type'] ?? null;
		if (!$this->db->begin_transaction()) {
			$error = "Failed to begin transaction";
		} else if (!$this->insertAsset->bind_param("sss", $path, $data, $type)) {
			$error = "Binding $path,$data,$type to insertAsset failed: {$this->db->error}";
		} else if (!$this->clearConflictingAssets->bind_param("s", $path)) {
			$error = "Binding $path to clearConflictingAssets failed: {$this->db->error}";
		} else if (!$this->clearConflictingAssets->execute()) {
			$error = "Executing clearConflictingAssets failed: {$this->db->error}";
			// TODO [#225]: Invalidate any cached descriptions referencing the cleared assets.
		} else if (!$this->insertAsset->execute()) {
			$error = "Executing insertAsset failed: {$this->db->error}";
		} else if ($this->insertAsset->affected_rows !== 1) {
			$error = "insertAsset affected {$this->insertAsset->affected_rows} rows instead of 1";
		}
		$id = $this->db->insert_id;
		if (!$error && $id === 0) {
			$error = "Got insert id 0";
		}

		if ($error) {
			log_err($error);
			$this->db->rollback();
			return $error;
		}
		return $this->db->commit() ? $id : "Failed to commit";
	}

	public function insertAsset(array $value): string|int {
		for ($i = 0; $i < 100; $i++) {
			try {
				if (is_numeric($result = $this->insertAssetOneshot($value))) {
					break;
				}
			} catch (mysqli_sql_exception $e) {
				$result = $e->getMessage();
				usleep(100000);
			}
		}
		return $result;
	}

	// TODO [$620f07ea4904a50008de134a]: Refactor db inserts to use exceptions.
	public function insertPet(array $pet, bool $inner = false): ?string {
		$error = null;
		$id = $pet['id'] ?? null;
		if ($id === null) {
			return "No id specified";
		}
		$name = $pet['name'] ?? null;
		$species = $pet['species'] ?? null;
		$breed = $pet['breed'] ?? null;
		$dob = $pet['dob'] ?? null;
		$sex = $pet['sex'] ?? null;
		$fee = $pet['fee'] ?? null;
		$photo = isset($pet['photo']) ? ($pet['photo']['key'] ?? null) : null;
		$description = isset($pet['description']) ? ($pet['description']['key'] ?? null) : null;
		$status = $pet['status'] ?? 1;
		$photos = $pet['photos'] ?? [];
		// bonded and friend will be set by setPair below
		$bonded = 0;
		$friend = null;
		$adoption_date = $pet['adoption_date'] ?: null;
		// order will be set by reorderPets
		$order = null;
		if (!($inner || $this->db->begin_transaction())) {
			$error = "Failed to begin transaction";
		} else {
			if ($photo) {
				$this->updateAsset($photo, $pet['photo']);
			}
			if ($description) {
				$this->updateAsset($description, $pet['description']);
			}
		}
		if (!$error) {
			if (!$this->insertPet->bind_param("ssissisiiiissi", $id, $name, $species, $breed, $dob, $sex, $fee, $photo,
					$description, $status, $bonded, $friend, $adoption_date, $order)) {
				$error =
						"Binding $id,$name,$species,$breed,$dob,$sex,$fee,$photo,$description,$status,$bonded,$friend,$adoption_date,$order to insertPet failed: {$this->db->error}";
			} else if (!$this->insertPet->execute()) {
				$error = "Executing insertPet failed: {$this->db->error}";
			} else if (!$this->deletePhotos->bind_param("s", $id)) {
				$error = "Failed to bind $id to deletePhotos: {$this->db->error}";
			} else if (!$this->deletePhotos->execute()) {
				$error = "Executing deletePhotos failed: {$this->db->error}";
			} else if ($pet['friend']) {
				$error = $this->insertPet($pet['friend'], true);
				if (!$error) {
					// Set bonded and friends.
					$friend_id = $pet['friend']['id'];
					$left_bonded = 1;
					$right_bonded = 2;
					if (!$this->setPair->bind_param("iss", $left_bonded, $friend_id, $id)) {
						$error = "Binding 1,$friend_id,$id to setPair failed: {$this->db->error}";
					} else if (!$this->setPair->execute()) {
						$error = "Executing setPair on left pet failed: {$this->db->error}";
					} else if (!$this->setPair->bind_param("iss", $right_bonded, $id, $friend_id)) {
						$error = "Binding 2,$id,$friend_id to setPair failed: {$this->db->error}";
					} else if (!$this->setPair->execute()) {
						$error = "Executing setPair on right pet failed: {$this->db->error}";
					}
				}
			}
		}
		if (!$error) {
			foreach ($photos as $photo) {
				if (!$photo || !$photo['key']) {
					continue;
				}
				// TODO [#162]: Add sort order to photos table.
				if (!$this->insertPhoto->bind_param("ss", $id, $photo['key'])) {
					$error = "Binding $id,{$photo['key']} to insertPhoto failed: {$this->db->error}";
					break;
				}
				if (!$this->insertPhoto->execute()) {
					$error = "Inserting photo $id,{$photo['key']} failed: {$this->db->error}";
					break;
				}
			}
		}
		if ($error) {
			log_err($error);
			$this->db->rollback();
			return $error;
		}
		if ($inner) {
			return null; // Don't commit the inner insert
		}
		return $this->db->commit() ? null : "Failed to commit";
	}

	public function updateAsset(int $key, array $value): ?string {
		$error = null;
		$path = ($value['path'] ?? null) ?: null;
		$data = isset($value['data']) ? serialize($value['data']) : null;
		$type = $value['type'] ?? null;
		if (!$this->db->begin_transaction()) {
			$error = "Failed to begin transaction";
		} else if (!$this->updateAsset->bind_param("sssi", $path, $data, $type, $key)) {
			$error = "Binding $path,$data,$type,$key to updateAsset failed: {$this->db->error}";
		} else {
			if (!$this->updateAsset->execute()) {
				$error = "Executing updateAsset failed: {$this->db->error}";
			} else if ($this->updateAsset->affected_rows !== 1) {
				$error = "updateAsset affected {$this->updateAsset->affected_rows} rows instead of 1";
			}
		}
		if ($error) {
			log_err($error);
			$this->db->rollback();
			return $error;
		}
		return $this->db->commit() ? null : "Failed to commit";
	}

	public function deletePet(string $key): ?string {
		$error = null;
		if (!$this->db->begin_transaction()) {
			$error = "Failed to begin transaction";
		} else if (!$this->deletePet->bind_param("s", $key)) {
			$error = "Binding $key to deletePet failed: {$this->db->error}";
		} else if (!$this->deletePet->execute()) {
			$error = "Executing deletePet failed: {$this->db->error}";
		} else if ($this->deletePet->affected_rows !== 1) {
			$error = "deletePet affected {$this->deletePet->affected_rows} rows instead of 1";
		}
		if ($error) {
			log_err($error);
			$this->db->rollback();
			return $error;
		}
		if (!$this->db->commit()) {
			return "Failed to commit";
		}
		return $this->db->commit() ? null : "Failed to commit";
	}
}
