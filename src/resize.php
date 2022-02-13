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


class ImageResizeException extends Exception {
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
		// TODO: Try getting size on a remote server.
		throw new ImageResizeException($e);
	}
}

/**
 * Resize an image to a specific height, maintaining the aspect ratio.
 * @param string $source The absolute path of the original image.
 * @param string $target The absolute path at which to save the resized image.
 * @param int $height The new height of the image.
 * @return void
 * @throws ImageResizeException
 */
function resize(string $source, string $target, int $height = 480): void {
	try {
		$original = new Imagick($source);
		$image = clone $original;
		$newWidth = (int) ($image->getImageWidth() / $image->getImageHeight() * $height);
		$image->resizeImage($newWidth, $height, Imagick::FILTER_LANCZOS, 1);
		$image->setImageCompression(Imagick::COMPRESSION_JPEG);
		$image->setImageCompressionQuality(80);
		$image->writeImage($target);
	} catch (ImagickException $e) {
		// TODO: Try resizing on a remote server.
		throw new ImageResizeException($e);
	}
}
