<?php
require_once 'api.php';

// This endpoint is for raw asset data. For metadata, use the assets endpoint.
// @todo implement the assets endpoint
endpoint(...[
    'get'          => $reject,
    'get_value'    => function($value) use ($db): Result {
        $asset = $db->getAssetByPath($value);
        if ($asset === null) {
            return new Result(404, "Asset $value not found");
        }
        header("Content-Type: " . $asset->getType());
        readfile($asset->absolutePath());
        exit(); // Exit here to avoid outputting JSON
    },
    'delete'       => $reject,
    'delete_value' => $reject,
]);