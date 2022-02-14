<?php
require_once 'api.php';
require_once "../../src/common.php";
require_once "$src/resize.php";

endpoint(...[
		'get' => function() use ($db): Result {
			set_time_limit(0);
			echo "{\n";
			$succeeded = 0;
			$failed = 0;
			foreach (glob(cached_assets() . "/*.jpg") as $filename) {
				$components = explode("_",basename($filename));
				$original = stored_assets() . "/${components[0]}";
				$height = intval(explode(".",$components[1])[0]);
				try {
					resize($original, $filename, $height);
				} catch (ImageResizeException $e) {
					echo "\"$filename\": " . json_encode($e) . ",\n";
					$failed++;
				}
				echo "\"$filename\": \"Success\",\n";
				$succeeded++;
			}
			echo "\"succeeded\": $succeeded, \"failed\": $failed}\n";
			exit(0);
		},
		'get_value' => $reject,
		'post' => $reject,
		'post_value' => $reject,
		'delete' => $reject,
		'delete_value' => $reject,
]);
