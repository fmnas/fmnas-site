<?php
/*
 * Copyright 2023 Google LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'api.php';

use Google\Cloud\Storage\StorageClient;
/**
 * Generate a v4 signed URL for uploading an object.
 *
 * @param string $bucketName The name of your Cloud Storage bucket.
 *        (e.g. 'my-bucket')
 * @param string $objectName The name of your Cloud Storage object.
 *        (e.g. 'my-object')
 */
function upload_object_v4_signed_url(string $bucketName, string $objectName): void
{
	$storage = new StorageClient();
	$bucket = $storage->bucket($bucketName);
	$object = $bucket->object($objectName);
	$url = $object->signedUrl(
	# This URL is valid for 15 minutes
			new \DateTime('15 min'),
			[
					'method' => 'PUT',
					'contentType' => 'application/octet-stream',
					'version' => 'v4',
			]
	);

	print('Generated PUT signed URL:' . PHP_EOL);
	print($url . PHP_EOL);
	print('You can use this URL with any user agent, for example:' . PHP_EOL);
	print("curl -X PUT -H 'Content-Type: application/octet-stream' " .
			'--upload-file my-file ' . $url . PHP_EOL);
}

// Get a signed url for uploading images to GCS.
endpoint(...[
		'get' => function() use ($db): Result {
			return new Result(200, 'foo');
		},
		'get_value' => $reject,
		'put' => $reject,
		'put_value' => $reject,
		'post' => $reject,
		'post_value' => $reject,
		'delete' => $reject,
		'delete_value' => $reject,
]);
