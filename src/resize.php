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
			CURLOPT_RETURNTRANSFER => true,
	]);
	$json = curl_exec($curl);
	// TODO [#328]: Check image-size for non-200 response code.
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
 * Resize multiple images remotely and in parallel.
 * @param array<FileSpec> $files
 * @return array<boolean|ImageResizeException> for each file, true for success and ImageResizeException for failure
 * @throws ImageResizeException
 */
// TODO [#279]: Parallel resizing is much slower than it should be.
function resizeMultiple(array $files, int $filter = Imagick::FILTER_LANCZOS): array {
	$filters = [
			Imagick::FILTER_POINT => 'point',
			Imagick::FILTER_BOX => 'box',
			Imagick::FILTER_TRIANGLE => 'triangle',
			Imagick::FILTER_HERMITE => 'hermite',
			Imagick::FILTER_HANNING => 'hanning',
			Imagick::FILTER_HAMMING => 'hamming',
			Imagick::FILTER_BLACKMAN => 'blackman',
			Imagick::FILTER_GAUSSIAN => 'gaussian',
			Imagick::FILTER_QUADRATIC => 'quadratic',
			Imagick::FILTER_CUBIC => 'cubic',
			Imagick::FILTER_CATROM => 'catrom',
			Imagick::FILTER_MITCHELL => 'mitchell',
			Imagick::FILTER_JINC => 'jinc',
			Imagick::FILTER_SINC => 'sinc',
			Imagick::FILTER_SINCFAST => 'sinc_fast',
			Imagick::FILTER_KAISER => 'kaiser',
			Imagick::FILTER_WELSH => 'welsh',
			Imagick::FILTER_PARZEN => 'parzen',
			Imagick::FILTER_BOHMAN => 'bohman',
			Imagick::FILTER_BARTLETT => 'bartlett',
			Imagick::FILTER_LAGRANGE => 'lagrange',
			Imagick::FILTER_LANCZOS => 'lanczos',
			Imagick::FILTER_LANCZOSSHARP => 'lanczos_sharp',
			Imagick::FILTER_LANCZOS2 => 'lanczos2',
			Imagick::FILTER_LANCZOS2SHARP => 'lanczos2_sharp',
			Imagick::FILTER_ROBIDOUX => 'robidoux',
			Imagick::FILTER_ROBIDOUXSHARP => 'robidoux_sharp',
			Imagick::FILTER_COSINE => 'cosine',
	];

	$results = [];
	$curls = [];
	foreach ($files as $index => $file) {
		if (!$file) {
			$curls[$index] = null;
			$results[$index] = new ImageResizeException("file is null");
			continue;
		}
		/** @var $file FileSpec */
		$curl = curl_init();
		if (!$curl) {
			$curls[$index] = null;
			continue;
		}
		curl_setopt_array($curl, [
				CURLOPT_URL => Config::$resize_image_endpoint,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => ["image" => new CURLFile($file->source), "height" => $file->height,
						"filter" => $filters[$filter]],
				CURLOPT_RETURNTRANSFER => true,
		]);
		$curls[$index] = $curl;
	}
	$multi = curl_multi_init();
	/** @noinspection PhpConditionAlreadyCheckedInspection */
	if (!$multi) {
		throw new ImageResizeException("Failed to initialize cURL");
	}
	foreach ($curls as $curl) {
		if ($curl !== null) {
			curl_multi_add_handle($multi, $curl);
		}
	}
	$running = null;
	do {
		curl_multi_exec($multi, $running);
	} while ($running);
	foreach ($curls as $curl) {
		if ($curl !== null) {
			curl_multi_remove_handle($multi, $curl);
		}
	}
	foreach ($curls as $index => $curl) {
		if ($curl === null) {
			$results[$index] = new ImageResizeException("Failed to initialize cURL");
			continue;
		}
		// TODO [#329]: Check resize-image for non-200 response code.
		if (!curl_errno($curl)) {
			if (!file_put_contents($files[$index]->target, curl_multi_getcontent($curl))) {
				$results[$index] = new ImageResizeException("Failed to write file");
			} else {
				$results[$index] = true;
			}
		} else {
			$results[$index] =
					new ImageResizeException("cURL Error: " . curl_error($curl) . "\n" . curl_multi_getcontent($curl));
		}
		curl_close($curl);
	}
	return $results;
}

/**
 * Run resize with the remote resize-image endpoint.
 * @param string $source The absolute path of the original image.
 * @param string $target The absolute path at which to save the resized image.
 * @param int $height The new max height of the image.
 * @return void
 * @throws ImageResizeException
 */
function remoteResize(string $source, string $target, int $height = 480, int $filter = Imagick::FILTER_LANCZOS): void {
	$filespec = new FileSpec();
	$filespec->source = $source;
	$filespec->target = $target;
	$filespec->height = $height;
	$result = resizeMultiple([$filespec], $filter)[0];
	if ($result !== true) {
		throw $result;
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
function resize(string $source, string $target, int $height = 480, int $filter = Imagick::FILTER_LANCZOS): void {
	try {
		remoteResize($source, $target, $height);
	} catch (ImageResizeException $e) {
		try {
			$image = new Imagick($source);
			$newHeight = min($image->getImageHeight(), $height);
			$newWidth = (int) ($image->getImageWidth() / $image->getImageHeight() * $newHeight);
			$image->resizeImage($newWidth, $newHeight, $filter, 1);
			$image->setImageCompression(Imagick::COMPRESSION_JPEG);
			$image->setImageCompressionQuality(90);
			$image->writeImage($target);
		} catch (ImagickException $e) {
			throw new ImageResizeException($e);
		}
	}
}

class FileSpec {
	public string $source;
	public string $target;
	public int $height;
}
