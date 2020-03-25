<?php
require_once("common.php");
require_once("db.php");

class Asset {
	public string $key; // Database key & storage filename
	public string $path; // Canonical pathname
	public array $data; // generic data associated with this asset
	private string $type; // MIME type
	private string $contents;

	private function absolutePath(): string {
		return stored_assets() . "/" . $this->key;
	}

	public function fetch(): string {
		$this->contents ??= file_get_contents($this->absolutePath());
		return $this->contents;
	}

	public function getType(): string {
		$this->type ??= mime_content_type($this->absolutePath());
		return $this->type;
	}

	public function setType(string $type): void {
		$this->type = $type;
	}
}