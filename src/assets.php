<?php

use JetBrains\PhpStorm\Pure;

require_once "common.php";
require_once "db.php";
require_once "resize.php";

class Asset {
	public int $key; // Database key & storage filename
	public ?string $path; // Canonical pathname
	public ?array $data; // generic data associated with this asset
	public ?string $type; // MIME type
	private ?string $contents;
	private ?array $size; // intrinsic size of image

	/**
	 * Parse handlebars/markdown into HTML and cache, or retrieve from cache
	 * @return string HTML code
	 */
	public function parse(array $context): string {
		self::createCacheDirectory();

		$filename = root() . "/public/assets/cache/$this->key.html";
		if (file_exists($filename)) {
			return file_get_contents($filename) . '<!-- Cached -->';
		}

		require_once "parser.php";
		$raw = $this->fetch();
		if (!($parsed = parse($raw, $context))) {
			log_err("Failed to parse asset with key $this->key");
			return $raw;
		}

		if (file_put_contents($filename, $parsed) === false) {
			log_err("Failed to write parsed version of $this->key to cache at $filename");
		}

		return $parsed;
	}

	public function getType(): string {
		$this->type = $this->type ?: mime_content_type($this->absolutePath());
		return $this->type;
	}

	public function setType(?string $type): void {
		$this->type = $type;
	}

	#[Pure] public function absolutePath(): string {
		return stored_assets() . "/" . $this->key;
	}

	public function fetch(): ?string {
		if (!isset($this->contents) || !trim($this->contents)) {
			if (!file_exists($this->absolutePath())) {
				log_err("Did not find stored asset with key $this->key at {$this->absolutePath()}");
				return null;
			}
			$this->contents = file_get_contents($this->absolutePath());
		}
		return $this->contents;
	}

	/**
	 * Create an image tag
	 * @param string|null $alt alt text
	 * @param bool $link Whether to wrap it in a link to the full size image
	 * @param bool $relative Whether the full size image is "in" the current directory
	 * @param int $height Desired height of 1x image or 0 for no scaling (default: 600)
	 * @return string img tag
	 */
	public function imgTag(?string $alt = "", bool $link = false, bool $relative = false, int $height = 600): string {
		if ($this->path) {
			$path = $relative ? basename($this->path) : '/' . $this->path;
		} else {
			$path = "/assets/stored/$this->key";
		}
		$tag = '';
		if ($link) {
			$tag .= "<a href=\"$path\">";
		}
		$tag .= '<img';
		if ($height !== 0 && $height <= $this->size()[1]) {
			$intrinsicHeight = $this->size()[1];
			$tag .= ' srcset="';
			$currentScale = 1;
			while ($currentScale * $height < $intrinsicHeight) {
				$tag .= $this->cachedImage($currentScale * $height);
				$tag .= " {$currentScale}x, ";
				if ($currentScale < 2) {
					$currentScale += 0.5;
				} else if ($currentScale < 4) {
					$currentScale += 1;
				} else {
					$currentScale *= 2;
				}
			}
			$tag .= "$path " . $intrinsicHeight / $height . "x\"";
		}
		$tag .= ' src="' . $path . '"';
		if ($alt) {
			$tag .= ' alt="' . htmlspecialchars($alt) . '"';
		}
		$tag .= '>';
		if ($link) {
			$tag .= '</a>';
		}
		return $tag;
	}

	private function size(): array {
		$this->size ??= size($this->absolutePath());
		return $this->size;
	}

	/**
	 * Cache an image at the specified height if not already done
	 * @param int $height desired height or 0 for no scaling
	 * @return string The path to the image, relative to root/public (e.g. "/assets/cache/1_0.jpg")
	 */
	private function cachedImage(int $height): string {
		if (!file_exists($this->absolutePath())) {
			log_err("Did not find stored image with key $this->key at {$this->absolutePath()}");
			return "";
		}

		$intrinsicHeight = $this->size()[1];
		$ratio = $this->size()[0] / $this->size()[1];

		if ($height === 0 || $height >= $intrinsicHeight) {
			$height = $intrinsicHeight;
		}

		$filename = "/assets/cache/" . $this->key . "_" . $height . ".jpg";
		$absoluteTarget = root() . "/public$filename";
		if (file_exists($absoluteTarget)) {
			return $filename;
		}

		try {
			resize($this->absolutePath(), $absoluteTarget, $height);
			return $filename;
		} catch (Exception $e) {
			log_err(print_r($e, true));
			return "/assets/stored/$this->key";
		}
	}
}
