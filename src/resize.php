<?php

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
		// TODO [#264]: Try getting size on a remote server.
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
		// TODO [#265]: Try resizing on a remote server.
		throw new ImageResizeException($e);
	}
}
