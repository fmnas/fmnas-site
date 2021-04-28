<?php
require_once "auth.php";
require_once "$src/db.php";
require_once "$src/generator.php";

class DatabaseWriter extends Database {
    private mysqli_stmt $addHistoryEntry; // @todo add version history
    private mysqli_stmt $setTransportDate;

    public function __construct() {
        parent::__construct();
        if (!($setTransportDate = $this->db->prepare("
			UPDATE config SET config_value=? WHERE config_key='transport_date' LIMIT 1
			"))) {
            log_err("Failed to prepare setTransportDate: {$this->db->error}");
        } else {
            $this->setTransportDate = $setTransportDate;
        }
    }

    public function setTransportDate(string $transportDate): ?string {
        $error = null;
        if (!$this->setTransportDate->bind_param("s", $transportDate)) {
            $error = "Binding transport date $transportDate to setTransportDate failed: {$this->db->error}";
        } else {
            if (!$this->setTransportDate->execute()) {
                $error = "Executing setTransportDate failed: {$this->db->error}";
            } else {
                if ($this->setTransportDate->affected_rows !== 1) {
                    $error = "setTransportDate affected {$this->setTransportDate->affected_rows} rows instead of 1";
                } else {
                    generate();
                }
            }
        }
        if ($error) {
            log_err($error);
        }
        return $error;
    }
}