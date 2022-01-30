<?php
require_once "../../src/common.php";
require_once "$src/db.php";

class DatabaseWriter extends Database {
	private mysqli_stmt $addHistoryEntry; // TODO [#5]: add version history
	private mysqli_stmt $setConfigValue;
	private mysqli_stmt $insertAsset;
	private mysqli_stmt $updateAsset;

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
			INSERT INTO assets VALUES(?, ?, ?, ?)
			"))) {
			log_err("Failed to prepare insertAsset: {$this->db->error}");
		} else {
			$this->insertAsset = $insertAsset;
		}
	}

	public function setConfigValue(string $key, string $value): ?string {
		global $src;

		$error = null;
		if (!$this->setConfigValue->bind_param("ss", $value, $key)) {
			$error = "Binding $key,$value to setConfigValue failed: {$this->db->error}";
		} else {
			if (!$this->setConfigValue->execute()) {
				$error = "Executing setConfigValue failed: {$this->db->error}";
			} else {
				if ($this->setConfigValue->affected_rows !== 1) {
					$error = "setConfigValue affected {$this->setConfigValue->affected_rows} rows instead of 1";
				} else {
					require_once "$src/generator.php";
				}
			}
		}
		if ($error) {
			log_err($error);
		}
		return $error;
	}

	public function insertAsset(array $value): string|int {
		$error = null;
		$path = ($value['path'] ?? null) ?: null;
		$data = isset($value['data']) ? serialize($value['data']) : null;
		$type = $value['type'] ?? null;
		if (!$this->insertAsset->bind_param("sss", $path, $data, $type)) {
			$error = "Binding $path,$data,$type to insertAsset failed: {$this->db->error}";
		} else {
			if (!$this->insertAsset->execute()) {
				$error = "Executing insertAsset failed: {$this->db->error}";
			} else if ($this->insertAsset->affected_rows !== 1) {
					$error = "insertAsset affected {$this->insertAsset->affected_rows} rows instead of 1";
			}
		}
		if (!$error && $this->db->insert_id === 0) {
			$error = "Got insert id {$this->db->insert_id}";
		}
		if ($error) {
			log_err($error);
		}
		return $error ?? $this->db->insert_id;
	}

	public function updateAsset(int $key, array $value): ?string {
		$error = null;
		$path = ($value['path'] ?? null) ?: null;
		$data = isset($value['data']) ? serialize($value['data']) : null;
		$type = $value['type'] ?? null;
		if (!$this->updateAsset->bind_param("sssi", $path, $data, $type, $key)) {
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
		}
		return $error;
	}
}
