<?php
/*
 * Copyright 2022 Google LLC
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

require_once __DIR__ . '/../secrets/config.php';

class ImageResizeException extends Exception {
}

// TODO [#274]: Google Cloud authentication for remote functions
// https://github.com/googleapis/google-auth-library-php
// https://cloud.google.com/docs/authentication/production#auth-cloud-implicit-php

/**
 * Run size with the remote image-size endpoint.
 * @param string $path The absolute path of the image.
 * @return int[] The width and height of the image.
 * @throws ImageResizeException
 */
function remoteSize(string $path): array {
	$curl = curl_init();
	if (!$curl) {
		throw new ImageResizeException("Failed to initialize cURL");
	}
	curl_setopt_array($curl, [
			CURLOPT_URL => Config::$image_size_endpoint,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => ["image" => new CURLFile($path)],
			CURLOPT_INFILESIZE => filesize($path),
			CURLOPT_RETURNTRANSFER => true,
	]);
	$json = curl_exec($curl);
	if (!curl_errno($curl)) {
		curl_close($curl);
		$result = json_decode($json);
		if ($result === null || !isset($result->width) || !isset($result->height)) {
			throw new ImageResizeException("$json");
		}
		return [$result->width, $result->height];
	} else {
		curl_close($curl);
		throw new ImageResizeException("cURL Error: " . curl_error($curl) . "\n$json");
	}
}

/**
 * Find the size of an image file.
 * @param string $path The absolute path of the image.
 * @return int[] The width and height of the image.
 * @throws ImageResizeException
 */
function size(string $path): array {
	try {
		$image = new Imagick($path);
		return [$image->getImageWidth(), $image->getImageHeight()];
	} catch (ImagickException $e) {
		return remoteSize($path);
	}
}

/**
 * Run resize with the remote resize-image endpoint.
 * @param string $source The absolute path of the original image.
 * @param string $target The absolute path at which to save the resized image.
 * @param int $height The new max height of the image.
 * @return void
 * @throws ImageResizeException
 */
function remoteResize(string $source, string $target, int $height = 480): void {
	$curl = curl_init();
	if (!$curl) {
		throw new ImageResizeException("Failed to initialize cURL");
	}
	curl_setopt_array($curl, [
		CURLOPT_URL => Config::$resize_image_endpoint,
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => ["image" => new CURLFile($source), "height" => $height],
		CURLOPT_INFILESIZE => filesize($source),
		CURLOPT_RETURNTRANSFER => true,
	]);
	$result = curl_exec($curl);
	if (!curl_errno($curl)) {
		file_put_contents($target, $result);
		curl_close($curl);
	} else {
		curl_close($curl);
		throw new ImageResizeException("cURL Error: " . curl_error($curl) . "\n$result");
	}
}

/**
 * Resize an image to a given maximum height, maintaining the aspect ratio.
 * @param string $source The absolute path of the original image.
 * @param string $target The absolute path at which to save the resized image.
 * @param int $height The new max height of the image.
 * @return void
 * @throws ImageResizeException
 */
function resize(string $source, string $target, int $height = 480): void {
	try {
		$image = new Imagick($source);
		$newHeight = min($image->getImageHeight(), $height);
		$newWidth = (int) ($image->getImageWidth() / $image->getImageHeight() * $newHeight);
		$image->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1);
		$image->setImageCompression(Imagick::COMPRESSION_JPEG);
		$image->setImageCompressionQuality(90);
		$image->writeImage($target);
	} catch (ImagickException $e) {
		remoteResize($source, $target, $height);
	}
}
