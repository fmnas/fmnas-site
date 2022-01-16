<?php
$src = "../../src"; // Wanted to get this from common.php, but it breaks PHPStorm
require_once "$src/common.php";
require_once "$src/db.php";

class DatabaseWriter extends Database {
    private mysqli_stmt $addHistoryEntry; // @todo add version history
    private mysqli_stmt $setConfigValue;

    public function __construct() {
        parent::__construct();
        if (!($setConfigValue = $this->db->prepare("
			UPDATE config SET config_value=? WHERE config_key=? LIMIT 1
			"))) {
            log_err("Failed to prepare setConfigValue: {$this->db->error}");
        } else {
            $this->setConfigValue = $setConfigValue;
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
}