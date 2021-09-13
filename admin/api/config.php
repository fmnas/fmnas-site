<?php
require_once 'api.php';

endpoint(...[
    'get'       => function() use ($db): Result {
        global $_G;
        return new Result(200, $_G);
    },
    'get_value' => function($key) use ($db): Result {
        global $_G;
        if (isset($_G[$key])) {
            return new Result(200, $_G[$key]);
        }
        return new Result(404, error: "Config key $key not found");
    },
    'put'       => $reject,
    'put_value' => function($key, $value) use ($db): Result {
        if (!is_string($value)) {
            return new Result(400, error: "Supplied value must be a string");
        }
        $error = $db->setConfigValue($key, $value);
        if ($error) {
            return new Result(500, error: $error);
        }
        regenerate();
        return new Result(200);
    },
    'delete'    => $reject,
]);