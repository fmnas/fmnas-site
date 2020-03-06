<?php
declare(strict_types = 1);

/**
 * The absolute path to the site root directory (containing admin/, public/, secrets/, src/)
 */
$root = dirname(__FILE__, 1);

/**
 * The absolute path to the src directory
 */
$src = "$root/src";

var_dump(getcwd()); // TODO: turn this into the assets path

/**
 * The relative path to the assets directory (from the file where execution started, i.e. the current page)
 */
$assets = "//assets";