<?php
require_once("common.php");
require_once("db.php");

class Asset {
	public string $key; // Database key & storage filename
	public ?string $path; // Canonical pathname
	public ?array $data; // generic data associated with this asset
	private ?string $type; // MIME type
	private ?string $contents;

	public function absolutePath(): string {
		return stored_assets() . "/" . $this->key;
	}

	public function fetch(): string {
		if (!$this->contents) {
			$this->contents = file_get_contents($this->absolutePath());
		}
		return $this->contents;
	}

	public function getType(): string {
		// If type not already known, detect the most common types from file extension
		if (!$this->type && $this->path) {
			if (endsWith($this->path, [".jpg", ".jpeg", ".jfif"])) {
				$this->type = "image/jpeg";
			} else if (endsWith($this->path, ".png")) {
				$this->type = "image/png";
			} else if (endsWith($this->path, ".gif")) {
				$this->type = "image/gif";
			} else if (endsWith($this->path, ".pdf")) {
				$this->type = "application/pdf";
			} else if (endsWith($this->path, ".txt")) {
				$this->type = "text/plain";
			} else if (endsWith($this->path, [".htm", ".html"])) {
				$this->type = "text/html";
			}
		}
		// If above fails to detect type, detect it from the file itself
		$this->type = $this->type ?: mime_content_type($this->absolutePath());
		return $this->type;
	}

	public function setType(string $type): void {
		$this->type = $type;
	}
}