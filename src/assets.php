<?php
require_once "common.php";
require_once "db.php";

class Asset {
    public string $key; // Database key & storage filename
    public ?string $path; // Canonical pathname
    public ?array $data; // generic data associated with this asset
    private ?string $type; // MIME type
    private ?string $contents;
    private ?array $size; // intrinsic size of image

    /**
     * Parse handlebars/markdown into HTML and cache, or retrieve from cache
     * @return string HTML code
     */
    public function parse(array $context): string {
        self::createCacheDirectory();

        if (!$this->getType() != "text/x-handlebars-template") {
            log_err("Warning: attempting to parse something with mime-type " . $this->getType() . " (not text/x-handlebars-template)");
        }

        $filename = root() . "/public/assets/cache/$this->key.html";
        if (file_exists($filename)) {
            return file_get_contents($filename);
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

    private static function createCacheDirectory(): void {
        @mkdir(root() . "/public/assets/cache", 0755, true);
    }

    public function getType(): string {
        // If type not already known, detect the most common types from file extension
        if (!$this->type && $this->path) {
            if (endsWith($this->path, [".jpg", ".jpeg", ".jfif"])) {
                $this->type = "image/jpeg";
            } else {
                if (endsWith($this->path, ".png")) {
                    $this->type = "image/png";
                } else {
                    if (endsWith($this->path, ".gif")) {
                        $this->type = "image/gif";
                    } else {
                        if (endsWith($this->path, ".pdf")) {
                            $this->type = "application/pdf";
                        } else {
                            if (endsWith($this->path, ".txt")) {
                                $this->type = "text/plain";
                            } else {
                                if (endsWith($this->path, [".htm", ".html"])) {
                                    $this->type = "text/html";
                                }
                            }
                        }
                    }
                }
            }
        }
        // If above fails to detect type, detect it from the file itself
        $this->type = $this->type ?: mime_content_type($this->absolutePath());
        return $this->type;
    }

    public function setType(?string $type): void {
        $this->type = $type;
    }

    public function absolutePath(): string {
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
        if ($height !== 0 && $height < $this->size()[1]) {
            $intrinsicWidth = $this->size()[0];
            $intrinsicHeight = $this->size()[1];
            $ratio = $this->size()[0] / $this->size()[1];
            $newWidth = round($ratio * $height);
            $tag .= ' srcset="';
            $currentScale = 1;
            while ($currentScale * $height < $intrinsicHeight) {
                $tag .= $this->cachedImage($currentScale * $height);
                $tag .= " {$currentScale}x, ";
                $currentScale += 0.5;
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
        $this->size ??= @getimagesize($this->absolutePath()) ?: [0, 0];
        return $this->size;
    }

    /**
     * Cache an image at the specified width if not already done
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

        $filename = "/assets/cache/" . $this->key . "_" . $height;
        switch ($this->getType()) {
        case "image/jpeg":
            $filename .= ".jpg";
            break;
        case "image/png":
            $filename .= ".png";
            break;
        case "image/gif":
            $filename .= ".gif";
            break;
        }
        $absoluteTarget = root() . "/public$filename";
        if (file_exists($absoluteTarget)) {
            return $filename;
        }

        if ($height === $intrinsicHeight) {
            if (!copy($this->absolutePath(), $absoluteTarget)) {
                log_err("Failed to copy {$this->absolutePath()} to $absoluteTarget");
                return "/assets/stored/$this->key";
            }
            return $filename;
        }

        switch ($this->getType()) {
        case "image/jpeg":
            $image = imagecreatefromjpeg($this->absolutePath());
            break;
        case "image/png":
            $image = imagecreatefrompng($this->absolutePath());
            break;
        case "image/gif":
            $image = imagecreatefromgif($this->absolutePath());
            break;
        default:
            log_err("Don't know how to resize {$this->getType()}");
            return "/assets/stored/$this->key";
        }

        $scaled = imagescale($image, round($height * $ratio), $height, IMG_BICUBIC);

        self::createCacheDirectory();
        $success = false;
        switch ($this->getType()) {
        case "image/jpeg":
            $success = imagejpeg($scaled, $absoluteTarget, 80);
            break;
        case "image/png":
            $success = imagepng($scaled, $absoluteTarget, 9);
            break;
        case "image/gif":
            $success = imagegif($scaled, $absoluteTarget);
            break;
        }
        if (!$success) {
            log_err("Failed to save image at $absoluteTarget");
            return "/assets/stored/$this->key";
        }

        return $filename;
    }
}