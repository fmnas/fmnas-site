<?php
require_once 'api.php';

endpoint(...[
    'get'       => function() use ($db): Result {
        global $_G;
        return new Result(200, $_G);
    },
    'get_value' => function($value) use ($db): Result {
        global $_G;
        if (isset($_G[$value])) {
            return new Result(200, $_G[$value]);
        }
        return new Result(404, "Config key $value not found");
    },
]);