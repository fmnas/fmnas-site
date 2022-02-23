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

namespace fmnas\Form;

use Closure;

/**
 * The FormConfig class contains data that shall not be output to the browser
 * used to process the form. After including the form processor, the global
 * $formConfig contains an instance of this class populated with defaults.
 * Update these values to control the behavior of the form processor.
 */
class FormConfig {
	/**
	 * Callback for when the form is submitted. This should be used to output
	 * a thank-you message, etc.
	 * @param array form data ($_POST or $_GET)
	 * @return void
	 */
	public Closure $confirm;

	/**
	 * Callback for when the form encounters an error during submission.
	 * @param FormException
	 * @return void
	 */
	public Closure $handler;

	/**
	 * Callback for when the form data is received but has not been processed yet.
	 * @param array form data ($_POST or $_GET)
	 * @return void
	 */
	public Closure $received;

	/**
	 * Whether to send an HTTP response and become a background process after calling $received.
	 */
	public bool $returnEarly = false;

	/**
	 * Closure that shall return configs for each copy of the form to email.
	 * In most cases, the returned iterable should be an array.
	 * @param array form data ($_POST or $_GET)
	 * @return iterable<FormEmailConfig>
	 */
	public Closure $emails;

	/**
	 * Closures to transform provided values into output values for the email.
	 * @param string
	 * @return string
	 * @var array<Closure> $transformers
	 */
	public array $transformers;

	/**
	 * Closures to transform provided file metadata arrays into output values for the email.
	 * @param array
	 * @return string
	 * @var array<Closure> $fileTransformers
	 */
	public array $fileTransformers;

	/**
	 * Closure to validate files using their metadata arrays.
	 * @param array
	 * @return bool
	 */
	public Closure $fileValidator;

	/**
	 * The HTTP method to check for a submitted form.
	 */
	public HTTPMethod $method;

	/**
	 * HTTP credentials needed for requests to the local server.
	 */
	public string $httpCredentials = "";

	/**
	 * Closure to update data before processing.
	 * Note that ["ignore_is_uploaded" => true] must be added to any files added/modified in $_FILES.
	 * @param array &$data form data
	 * @param array &$files $_FILES
	 * @return void
	 */
	public Closure $updateData;

	public function __construct() {
		$this->confirm = function(array $formData): void {
			?>
			<!DOCTYPE html>
			<title>Thank you!</title>
			<meta charset="UTF-8">
			<meta name="robots" content="noindex,nofollow">
			<h1>Thank You</h1>
			<p>We have received your form submission.
			<p><a href="/">Back to homepage</a>
			<!--
<?php
			var_dump($formData);
			?>
    -->
			<?php
		};
		$this->handler = function(FormException $e): void {
			http_response_code(500);
			?>
			<!DOCTYPE html>
			<title>Error</title>
			<meta charset="UTF-8">
			<meta name="robots" content="noindex,nofollow">
			<h1>Error <?=$e->getCode()?></h1>
			<p>Something went wrong submitting the form: <?=$e->getMessage()?>
			<p><a href="/">Back to homepage</a>
			<!--
<?php
			var_dump($e);
			?>
    -->
			<?php
		};

		$this->emails = function(array $formData): array {
			$email = new EmailAddress('webmaster@' . $_SERVER['HTTP_HOST']);
			$config = new FormEmailConfig($email, [$email], 'Form Data');
			return [$config];
		};
		$this->method = HTTPMethod::EITHER;
		$this->transformers = [
				"email-link" => function(string $email): string {
					return "<a href=\"mailto:$email\">$email</a>";
				},
				"tel-link" => function(string $tel): string {
					return "<a href=\"tel:$tel\">$tel</a>";
				},
				"link" => function(string $url): string {
					return "<a href=\"$url\">$url</a>";
				},
		];
		$this->fileTransformers = [
				"name" => function(array $metadata): string {
					return $metadata["name"];
				},
				"type" => function(array $metadata): string {
					return $metadata["type"];
				},
				"size" => function(array $metadata): string {
					return strval($metadata["size"]);
				},
				"path" => function(array $metadata): string {
					return $metadata["path"] ?? $metadata["tmp_name"];
				},
				"dump" => function(array $metadata): string {
					return print_r($metadata, true);
				},
				"image" => function(array $metadata): string {
					return '<img src="data:' . $metadata["type"] . ';base64,' .
							base64_encode(file_get_contents($metadata["tmp_name"])) .
							'" alt="' . htmlspecialchars($metadata["name"]) . '"/>';
				},
				"thumbnail" => function(array $metadata): string {
					$image = @imagecreatefromstring(file_get_contents($metadata["tmp_name"]));
					if (!$image) {
						return $metadata["name"];
					}
					$width = imagesx($image);
					$height = imagesy($image);
					$newHeight = 64;
					$newWidth = floor($width / $height * $newHeight);
					$thumb = imagecreatetruecolor($newWidth, $newHeight);
					imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
					ob_start();
					imagejpeg($thumb);
					$data = ob_get_clean();
					return '<img src="data:image/jpeg;base64,' . base64_encode($data) . '" alt="' .
							htmlspecialchars($metadata["name"]) . '" title="' . htmlspecialchars($metadata["name"]) . '"/>';
				},
		];
		$this->fileValidator = function(array $metadata): bool {
			return !$metadata["error"];
		};
		$this->received = function(array $formData): void {

		};
		$this->updateData = function(array &$data, array &$files): void {};
	}
}
