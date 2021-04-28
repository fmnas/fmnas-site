<?php
require 'api.php';

endpoint(...[
    'get'       => function(): Result {
        if (isset($_GET['species'])) {
            return new Result(200, ['all', 'the', $_GET['species']]);
        }
        return new Result(200, ['all', 'the', 'listings']);
    },
    'get_value' => function($value): Result {
        return new Result(200, $value);
    },
]);