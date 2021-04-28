<?php
require_once "../src/common.php";
require_once "$src/db.php";
require_once "$src/assets.php";
$db ??= new Database();
/* @var $path string */
/* @var $asset Asset */
$asset = $db->getAssetByPath($path);
if ($asset !== null) {
    if (!file_exists($asset->absolutePath())) {
        log_err("Asset id $asset->key exists but not found at path $asset->path");
        require_once "$src/errors/404.php";
    }
    header("Content-Type: " . $asset->getType());

    // Allow caching of images
    if (startsWith($asset->getType(), "image/")) {
        $seconds_to_cache = 60 * 60 * 24 * 30; // 30 days
        $ts               = gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT";
        header("Expires: $ts");
        header("Pragma: cache");
        header("Cache-Control: max-age=$seconds_to_cache");
    }

    readfile($asset->absolutePath());

    // Exit if this is indeed an asset
    exit();
}