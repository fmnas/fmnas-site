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
use DOMDocument;
use DOMElement;
use DOMException;
use DOMText;
use Exception;
use JetBrains\PhpStorm\Pure;
use Masterminds\HTML5;

class FormProcessor {
	public Closure $collector;

	// Useful for debugging.
	// TODO [#366]: Check that $PERSIST_TEMP_FILES is false when merging.
	private bool $PERSIST_TEMP_FILES = true;

	public function __construct(
			private FormConfig $formConfig,
	) {
		$this->collector = function() {
			$this->collectForm();
		};
	}

	/**
	 * This function is called after the form page is rendered.
	 * If the form data has already been submitted, the rendered HTML is parsed
	 * and used to generate and send the email.
	 * Otherwise, the rendered HTML is output to the browser.
	 */
	public function collectForm(): void {
		// Get the submitted POST or GET data.
		$receivedData = match ($this->formConfig->method) {
			HTTPMethod::GET => $_GET,
			HTTPMethod::POST => $_POST,
			HTTPMethod::EITHER => $_POST ?: $_GET,
		};

		$stage = $receivedData["_form_stage"] ?? ($receivedData || $_FILES ? "receiveData" : "displayForm");

		try {
			switch ($stage) {
			case "receiveData":
				$this->receiveData($receivedData);
				break;
			case "displayForm":
				$this->displayForm($receivedData);
				break;
			case "processForm":
				if (count($receivedData) === 1) {
					// This request was made from the return-early stage of receiveData,
					// and the form data will be serialized in an attached file.
					$data = unserialize(file_get_contents($_FILES["data"]["tmp_name"]));
					$_FILES = $data["_form_files"];
					$this->processForm($data);
					if (!$this->PERSIST_TEMP_FILES) {
						foreach ($_FILES as $file) {
							// Explicitly delete the files, as they were moved by the previous stage to be persistent.
							if (is_array($file["tmp_name"])) {
								foreach ($file["tmp_name"] as $filename) {
									@unlink($filename);
								}
							} else {
								@unlink($file["tmp_name"]);
							}
						}
						@unlink($data["_tmp_file"]);
					}
				} else {
					$this->processForm($receivedData);
				}
				break;
			default:
				throw new Exception("Unrecognized form stage $stage");
			}
		} catch (Exception $e) {
			($this->formConfig->handler)(new FormException($e));
		}
	}

	/**
	 * Find the HTML of the form, either from the form data "_form_html" or from the output buffer.
	 * @param $data array Form data
	 * @return string
	 */
	private function collectHtml(array &$data): string {
		return $data["_form_html"] ??= ob_get_clean();
	}

	/**
	 * Second stage of form execution: receive the data and pass it to processForm, optionally after returning
	 * an HTTP response with returnEarly.
	 * @param $data array Form data
	 * @throws DOMException
	 * @throws \PHPMailer\PHPMailer\Exception
	 */
	private function receiveData(array &$data) {
		$this->collectHtml($data);
		($this->formConfig->received)($data);
		if ($this->formConfig->returnEarly) {
			// Move uploaded files to be persistent.
			foreach ($_FILES as &$file) {
				if (is_array($file["tmp_name"])) {
					foreach ($file["tmp_name"] as &$oldname) {
						$newname = tempnam(sys_get_temp_dir(), "PERSIST_");
						@move_uploaded_file($oldname, $newname);
						$oldname = $newname;
					}
				} else {
					$newname = tempnam(sys_get_temp_dir(), "PERSIST_");
					@move_uploaded_file($file["tmp_name"], $newname);
					$file["tmp_name"] = $newname;
				}
			}

			$data["_form_files"] = $_FILES;
			$tempfile = tempnam(sys_get_temp_dir(), "PERSIST_");
			$data["_tmp_file"] = $tempfile;
			file_put_contents($tempfile, serialize($data));

			// Request processing in the background.
			$pipes = [];
			$scheme = ($_SERVER["HTTPS"] ?? "off") === "on" ? "https" : "http";
			$host = $_SERVER["HTTP_HOST"];
			$server = $_SERVER["SERVER_NAME"];
			if ($host !== $server) {
				error_log("Host $host doesn't match server $server - refusing to request background processing.");
				$this->processForm($data);
				return;
			}
			$uri = $_SERVER["REQUEST_URI"];
			$relative = parse_url($uri, PHP_URL_PATH);
			$path = "$scheme://$host$relative";
			$command =
					"curl -v -u \"{$this->formConfig->httpCredentials}\" -F '_form_stage=processForm' -F 'data=@$tempfile' $path";
			proc_close(proc_open("$command &", [], $pipes));
		} else {
			$this->processForm($data);
		}
	}

	private function displayForm(array &$data) {
		$html = $this->collectHtml($data);

		// Look for a good place to put the injected CSS.
		$separator = "";
		$after = false; // Whether to place the CSS after the separator instead of before.
		$halves = [$html, ""];
		/** @noinspection HtmlRequiredLangAttribute */
		foreach ([["</head>", false], ["<body", false], ["<head", true], ["</title>", true], ["<!DOCTYPE html>", true],
				["<html>", true]] as $candidate) {
			if (contains($html, $candidate[0])) {
				$separator = $candidate[0];
				$after = $candidate[1];
				$halves = explode($candidate[0], $html, 2);
				break;
			}
		}

		// Stuff before the injected CSS.
		echo $halves[0];
		if ($after) {
			echo $separator;
		}

		// Build and echo the injected CSS.
		$attributes = ["data-hidden", "data-foreach", "data-foreach-config", "data-if", "data-if-config", "data-value",
				"data-value-config", "data-href", "data-href-config"];
		$selectors = [];
		foreach ($attributes as $attribute) {
			$selectors[] =
					"*[$attribute]:not([data-hidden='0']):not([data-hidden='false']):not([data-hidden='false' i])";
		}
		echo "<style>" . implode(",", $selectors) . "{display: none !important;}</style>";

		// Stuff after the injected CSS.
		if (!$after) {
			echo $separator;
		}
		echo $halves[1];
	}

	/**
	 * Send emails containing the submitted form data.
	 * @param array $data Raw form data ($_GET or $_POST).
	 * @throws \PHPMailer\PHPMailer\Exception
	 * @throws DOMException
	 */
	private function processForm(array $data): void {
		$html = $this->collectHtml($data);
		$data["_FORM_DEDUPLICATION_METADATA_"] = date("Ymd") . (@md5_file(__FILE__) ?: "") . md5($html);

		$this->validateFiles();

		foreach (($this->formConfig->emails)($data) as $emailConfig) {
			/** @var $emailConfig FormEmailConfig */
			$renderedForm = $this->renderForm($data, $html, $emailConfig);

			$this->sendEmail($emailConfig, $renderedForm);
		}
	}

	/**
	 * Remove invalid files from $_FILES.
	 */
	private function validateFiles(): void {
		$removeInputs = [];
		foreach ($_FILES as $inputName => &$fileArray) {
			if (!isset($fileArray["size"])) {
				echo "Warning: got a bogus file array!";
				var_dump($fileArray);
				continue;
			}
			$multiple = is_array($fileArray["size"]);
			$transformed = $this->transformFileArray($fileArray);
			$invalidFiles = [];
			foreach ($transformed as $index => $file) {
				if (!($this->formConfig->fileValidator)($file)) {
					$invalidFiles[] = $index;
				}
			}
			if (count($invalidFiles)) {
				if (!$multiple) {
					$removeInputs[] = $inputName;
				} else {
					foreach ($fileArray as &$fileValuesArray) {
						$newValues = [];
						foreach ($fileValuesArray as $index => $value) {
							if (!in_array($index, $invalidFiles)) {
								$newValues[] = $value;
							}
						}
						$fileValuesArray = $newValues;
					}
				}
			}
		}
		foreach ($removeInputs as $input) {
			unset($_FILES[$input]);
		}
	}

	/**
	 * Convert a file array with multiple files to an array of file arrays.
	 * @param array $metadata A PHP-style file metadata array (like $_FILES["something"])
	 * @return array A sane file metadata array
	 */
	#[Pure] private function transformFileArray(array $metadata): array {
		if (!isset($metadata["size"])) {
			return [];
		}
		if (!is_array($metadata["size"])) {
			return [$metadata];
		}
		$files = [];
		for ($i = 0; $i < count($metadata["size"]); $i++) {
			$file = [];
			foreach ($metadata as $key => $values) {
				$file[$key] = $values[$i] ?? null;
			}
			$files[] = $file;
		}
		return $files;
	}

	/**
	 * Render the HTML to send in an email.
	 * @param array $data The submitted form data.
	 * @param FormEmailConfig $emailConfig A config with additional context for rendering.
	 * @param string $html The server-rendered HTML of the empty form.
	 * @return RenderedEmail Rendered email containing the form values.
	 * @throws Exception
	 * @throws DOMException
	 * @noinspection PhpMissingBreakStatementInspection
	 */
	private function renderForm(array $data, string $html, FormEmailConfig $emailConfig): RenderedEmail {
		$files = $this->applyFileConversions($emailConfig);
		$attachments = $this->collectAttachments($files);

		$values = $emailConfig->values ?? [];
		$html5 = new HTML5(["disable_html_ns" => true]);
		$dom = $html5->loadHTML($html);
		$forms = $dom->getElementsByTagName('form');
		$originalForm = null;
		if ($forms->length === 0) {
			throw new Exception('No form elements were found in the rendered HTML.');
		}
		if ($forms->length > 1) {
			foreach ($forms as $form) {
				/** @var $form DOMElement */
				if ($form->getAttribute("id") === $data["form_id"]) {
					$originalForm = $form;
					break;
				}
			}
			if (!$originalForm) {
				throw new Exception('Multiple form elements were found in the rendered HTML. ' .
						'Please add a hidden input with name="form_id" matching the id of submitted form.');
			}
		} else {
			$originalForm = $forms[0];
		}
		/** @var $originalForm DOMElement */

		// Replace form element with article element.
		$form = $dom->createElement("article");
		DOMHelpers::moveChildren($originalForm, $form);
		DOMHelpers::copyAttributes($originalForm, $form, "method", "enctype");
		$form->setAttribute("data-type", "form");
		$originalForm->parentNode?->replaceChild($form, $originalForm);

		// Replace input elements with span elements.
		foreach (DOMHelpers::collectElements($form, "input") as $input) {
			/** @var $input DOMElement */
			$span = $dom->createElement("span");
			$span->setAttribute("data-type", "input");
			$inputName = $input->getAttribute("name");
			DOMHelpers::copyAttributes($input, $span, "value", "required", "type", "accept", "capture", "multiple", "form",
					"checked",
					"pattern", "name", "disabled");
			$inputType = $input->getAttribute("type");
			$span->setAttribute("data-input-type", $inputType);
			switch ($inputType) {
			case 'file':
				$span->setAttribute("data-value", $inputName);
				break;
			case 'button':
			case 'submit':
			case 'reset':
			case 'password':
			case 'image':
			case 'hidden':
				if (!$input->hasAttribute("data-remove")) {
					$span->setAttribute("data-remove", "1");
					break;
				}
				// Fall through to standard input handler in case it isn't to be removed.
			case 'checkbox':
			case 'radio':
				$span->setAttribute("data-selected",
						($input->getAttribute("value") ?: "on") === ($data[$inputName] ?? false) ? "1" : "0");
				// Fall through to standard input handler.
			default:
				if (endsWith($inputName, "[]") && !$input->hasAttribute("data-remove")) {
					// Remove repeated inputs by default.
					$span->setAttribute("data-remove", "1");
				}
				$span->nodeValue = htmlspecialchars(strval($data[$inputName] ?? $input->getAttribute("value")));
			}
			$input->parentNode?->replaceChild($span, $input);
		}

		// Replace select elements with span elements.
		foreach (DOMHelpers::collectElements($form, "select") as $select) {
			/** @var $select DOMElement */
			$span = $dom->createElement("span");
			$span->setAttribute("data-type", "select");
			$inputName = $select->getAttribute("name");
			DOMHelpers::copyAttributes($select, $span, "value", "required", "form", "name", "disabled");
			$selected = $data[$inputName] ?? "";
			foreach (DOMHelpers::collectElements($select, "option") as $option) {
				/** @var $option DOMElement */
				if (($option->getAttribute("value") ?: $option->nodeValue) === $selected) {
					$span->nodeValue = htmlspecialchars(strval($option->nodeValue ?: $selected));
					break;
				}
			}
			$select->parentNode?->replaceChild($span, $select);
		}

		// Replace textarea elements with pre elements.
		foreach (DOMHelpers::collectElements($form, "textarea") as $textarea) {
			/** @var $textarea DOMElement */
			$pre = $dom->createElement("pre");
			$pre->setAttribute("data-type", "textarea");
			$inputName = $textarea->getAttribute("name");
			DOMHelpers::copyAttributes($textarea, $pre, "value", "required", "form", "name", "disabled");
			$pre->nodeValue = htmlspecialchars(strval($data[$inputName] ?? $textarea->nodeValue));
			$textarea->parentNode?->replaceChild($pre, $textarea);
		}

		// Replace fieldset elements with section elements with h3 headers.
		foreach (DOMHelpers::collectElements($form, "fieldset") as $fieldset) {
			/** @var $fieldset DOMElement */

			// Ideally a fieldset should contain exactly one legend, but you never know.
			$legends = DOMHelpers::collectElements($fieldset, "legend");
			if (count($legends)) {
				/** @var $legend DOMElement */
				$legend = $legends[0];
				$h3 = $dom->createElement("h3");
				DOMHelpers::copyAttributes($legend, $h3);
				$h3->setAttribute("data-type", "legend");
				$h3->nodeValue = $legend->nodeValue;
				$legend->parentNode?->replaceChild($h3, $legend);
			}
			foreach ($legends as $legend) {
				// Mark all legends for removal.
				if (!$legend->hasAttribute("data-remove")) {
					$legend->setAttribute("data-remove", "1");
				}
			}

			$section = $dom->createElement("section");
			DOMHelpers::copyAttributes($fieldset, $section, "form");
			$section->setAttribute("data-type", "fieldset");
			DOMHelpers::moveChildren($fieldset, $section);
			$fieldset->parentNode?->replaceChild($section, $fieldset);
		}

		// Replace label elements with span elements.
		foreach (DOMHelpers::collectElements($dom, "label") as $label) {
			/** @var $label DOMElement */
			$type = null;
			$selected = null;
			// Look for input elements within the label to determine type and selected.
			if ($childInput = DOMHelpers::collectElements($label, "*", DOMHelpers::has("data-type"))[0] ?? false) {
				$elementType = $childInput->getAttribute("data-type");
				$type = $elementType === "input" ? $childInput->getAttribute("data-input-type") : $elementType;
				$selected = $childInput->getAttribute("data-selected");
			}
			if (!$type && $label->getAttribute("for")) {
				$input = DOMHelpers::collectElements($dom, "*", DOMHelpers::attr("id", $label->getAttribute("for")))[0] ?? null;
				$type = $input?->getAttribute("data-type") === "input" ? $input->getAttribute("data-input-type") :
						$input?->getAttribute("data-type") ?? $input?->tagName;
				$selected = $input?->getAttribute("data-selected");
			}
			// Wrap plain text in an inner span.
			foreach ($label->childNodes as $childNode) {
				if ($childNode instanceof DOMText) {
					$innerSpan = $dom->createElement("span");
					$innerSpan->setAttribute("data-type", "label-text");
					$innerSpan->nodeValue = trim($childNode->nodeValue);
					$childNode->replaceWith($innerSpan);
				}
			}
			$span = $dom->createElement("span");
			$span->setAttribute("data-type", "label");
			if ($type) {
				$span->setAttribute("data-input-type", $type);
			}
			if ($selected !== null && $selected !== "") {
				$span->setAttribute("data-selected", $selected);
			}
			DOMHelpers::copyAttributes($label, $span, "for");
			DOMHelpers::moveChildren($label, $span);
			$label->replaceWith($span);
		}

		// Replace button elements with span elements (hidden by default).
		foreach (DOMHelpers::collectElements($form, "button") as $button) {
			/** @var $button DOMElement */
			$span = $dom->createElement("span");
			$span->setAttribute("data-type", "button");
			DOMHelpers::copyAttributes($button, $span, "value", "required", "type", "name", "formaction", "disabled");
			if (!$span->hasAttribute("data-remove")) {
				$span->setAttribute("data-remove", "1");
			}
			$button->parentNode?->replaceChild($span, $button);
		}

		// Mark all script elements with data-remove.
		foreach (DOMHelpers::collectElements($dom, "script") as $script) {
			/** @var $script DOMElement */
			if (!$script->hasAttribute("data-remove")) {
				$script->setAttribute("data-remove", "1");
			}
		}

		// Mark all non-stylesheet link elements with data-remove.
		foreach (DOMHelpers::collectElements($dom, "link", DOMHelpers::not(DOMHelpers::attr("rel", "stylesheet"))) as $link)
		{
			/** @var $link DOMElement */
			if (!$link->hasAttribute("data-remove")) {
				$link->setAttribute("data-remove", "1");
			}
		}

		// Process data-foreach elements.
		foreach (DOMHelpers::collectElements($dom, "*", DOMHelpers::has("data-foreach", "data-foreach-config")) as $element)
		{
			/** @var $element DOMElement */

			$arr = $data[$element->getAttribute("data-foreach")] ??
					$values[$element->getAttribute("data-foreach-config")] ?? null;
			$fileInput = false;
			if ($arr === null) {
				$fileInput = true;
				$arr = $this->transformFileArray($files[$element->getAttribute("data-foreach")] ?? []);
			}
			if (!is_array($arr)) {
				$arr = [$arr];
			}

			$newNodes = [];

			foreach ($arr as $value) {
				$clone = $element->cloneNode(true);
				if ($element->hasAttribute("data-as")) {
					$this->applyDataValues($clone, [...$data, $element->getAttribute("data-as") => $value], $values, $files);
				} else {
					if ($fileInput) {
						$transformer =
								$this->formConfig->fileTransformers[$element->getAttribute("data-file-transformer") ?: "name"];
						$clone->nodeValue = strval($transformer($value));
					} else {
						$clone->nodeValue = strval($value);
					}
				}
				$newNodes[] = ($clone);
			}

			if (count($newNodes)) {
				$element->replaceWith(...$newNodes);
			} else {
				$element->remove();
			}
		}

		// Process data-if elements.
		foreach (DOMHelpers::collectElements($dom, "*", DOMHelpers::has("data-if", "data-if-config")) as $element) {
			/** @var $element DOMElement */

			$lhs = $data[$element->getAttribute("data-if")] ?? $values[$element->getAttribute("data-if-config")] ?? null;

			$rhs = true;
			if ($element->hasAttribute("data-rhs")) {
				$rhs = $element->getAttribute("data-rhs");
			} else if ($element->hasAttribute("data-rhs-config")) {
				$rhs = $values[$element->getAttribute("data-rhs-config")] ?? null;
			} else if ($element->hasAttribute("data-rhs-value")) {
				$rhs = $data[$element->getAttribute("data-rhs-value")] ?? null;
			} else if ($element->hasAttribute("data-rhs-value-config")) {
				$rhs = $values[$element->getAttribute("data-rhs-value-config")] ?? null;
			}
			if ($rhs === "false") {
				$rhs = false;
			}

			$result = match ($element->getAttribute("data-operator")) {
				"lt" => $lhs < $rhs,
				"gt" => $lhs > $rhs,
				"le" => $lhs <= $rhs,
				"ge" => $lhs >= $rhs,
				"ne" => $lhs != $rhs,
				default => $lhs == $rhs,
			};

			if (!$result) {
				$element->setAttribute("data-remove", "1");
			}
		}

		// Remove all elements marked with data-remove.
		foreach (DOMHelpers::collectElements($dom, "*", DOMHelpers::truthy("data-remove")) as $element) {
			/** @var $element DOMElement */
			$element->parentNode?->removeChild($element);
		}

		$this->applyDataValues($dom, $data, $values, $files);

		// Apply data transformations.
		foreach (DOMHelpers::collectElements($dom, "*", DOMHelpers::has("data-transformer")) as $element) {
			/** @var $element DOMElement */
			if ($element->hasAttribute("data-transformer-if") || $element->hasAttribute("data-transformer-if-config")) {
				$lhs = $data[$element->getAttribute("data-transformer-if")] ??
						$values[$element->getAttribute("data-transformer-if-config")] ?? null;

				$rhs = true;
				if ($element->hasAttribute("data-transformer-rhs")) {
					$rhs = $element->getAttribute("data-transformer-rhs");
				} else if ($element->hasAttribute("data-transformer-rhs-value")) {
					$rhs = $data[$element->getAttribute("data-transformer-rhs-value")] ?? null;
				} else if ($element->hasAttribute("data-transformer-rhs-config")) {
					$rhs = $values[$element->getAttribute("data-transformer-rhs-config")] ?? null;
				}

				$result = match ($element->getAttribute("data-transformer-operator")) {
					"lt" => $lhs < $rhs,
					"gt" => $lhs > $rhs,
					"le" => $lhs <= $rhs,
					"ge" => $lhs >= $rhs,
					"ne" => $lhs != $rhs,
					default => $lhs == $rhs,
				};

				if (!$result) {
					continue; // Don't apply the transformation to this element.
				}
			}
			$transformer = $element->getAttribute("data-transformer");
			if (isset($this->formConfig->transformers[$transformer])) {
				if ($element->hasAttribute("data-href") || $element->hasAttribute("data-href-config")) {
					$element->setAttribute("href", $this->formConfig->transformers[$transformer]($element->getAttribute("href")));
				} else {
					$innerHTML = "";
					while ($element->hasChildNodes()) {
						$innerHTML .= $dom->saveHTML($element->firstChild);
						$element->removeChild($element->firstChild);
					}
					$transformed = $this->formConfig->transformers[$transformer]($innerHTML);
					$fragment = $element->ownerDocument->createDocumentFragment();
					$fragment->appendXML($transformed);
					$element->appendChild($fragment);
				}
			} else {
				echo "Warning: transformer $transformer not found; leaving value untransformed.";
			}
		}

		// Apply any custom transformation.
		if ($emailConfig->emailTransformation) {
			if ($transformedEmail = ($emailConfig->emailTransformation)($dom, $attachments)) {
				return new RenderedEmail($transformedEmail, $attachments);
			}
		}

		return new RenderedEmail($dom->saveHTML(), $attachments);
	}

	/**
	 * Apply the fileConverter, hashFilenames, and fileDir conversions to files, and add "input" and "attached" to files.
	 * @param FormEmailConfig $emailConfig
	 * @return array The updated file metadata array
	 */
	private function applyFileConversions(FormEmailConfig $emailConfig): array {
		if ($emailConfig->globalConversion) {
			$files = &$_FILES;
		} else {
			$files = $_FILES;
		}

		foreach ($files as $inputName => &$fileArray) {
			if ($inputName[0] === '_') {
				// Discard internal files.
				$file["attached"] = false;
				$this->updateFile($fileArray, 0, $file);
				continue;
			}
			$transformed = $this->transformFileArray($fileArray);
			// Apply filesConverter.
			if ($emailConfig->filesConverter !== null) {
				($emailConfig->filesConverter)($transformed);
			}
			foreach ($transformed as $index => &$file) {
				$file["input"] = $inputName;

				// Apply fileConverter.
				if ($emailConfig->fileConverter !== null) {
					($emailConfig->fileConverter)($file);
				}

				// Apply hashFilenames.
				$hash = is_callable($emailConfig->hashFilenames) ? ($emailConfig->hashFilenames)($file) :
						$emailConfig->hashFilenames;
				/** @var $hash HashOptions */
				if ($hash !== HashOptions::NO) {
					$ext = pathinfo($file["name"], PATHINFO_EXTENSION);
					$file["hash"] = sha1_file($file["tmp_name"]);
					if ($ext) {
						$file["hash"] .= ".$ext";
					}
					$file["original"] = $file["name"];
				}
				if ($hash === HashOptions::EMAIL_ONLY || $hash === HashOptions::YES) {
					$file["name"] = $file["hash"];
				}

				// Apply fileDir.
				$path = is_callable($emailConfig->fileDir) ? ($emailConfig->fileDir)($file) : $emailConfig->fileDir;
				if (!is_string($path)) {
					echo "Warning: got non-string path for file; ignoring.";
					var_dump($path, $file);
					$path = "";
				}
				if ($path) {
					if (!endsWith($path, "/")) {
						$path .= "/";
					}
					if ($hash === HashOptions::SAVED_ONLY || $hash === HashOptions::YES) {
						$path .= $file["hash"];
					} else if ($hash === HashOptions::EMAIL_ONLY) {
						$path .= $file["original"];
					} else if ($hash === HashOptions::NO) {
						$path .= $file["name"];
					}
					if (copy($file["tmp_name"], $path)) {
						$file["path"] = $path;
					} else {
						echo "Warning: Failed to copy ${file["tmp_name"]} to $path.";
					}
				}

				// Apply attachFiles.
				$attach =
						is_callable($emailConfig->attachFiles) ? ($emailConfig->attachFiles)($file) : $emailConfig->attachFiles;
				$file["attached"] = !!$attach;

				$this->updateFile($fileArray, $index, $file);
			}
		}

		// If $files is a reference, this still returns a copy because PHP. https://3v4l.org/T5cKC
		return $files;
	}

	/**
	 * Propagate changes to a file from a transformed files array back to the original files array.
	 * @param array $files A PHP-style file metadata array like ($_FILES["something"])
	 * @param int $index The index of the file to update
	 * @param array $file The file (in transformFileArray) to put in $files
	 * @return void
	 */
	private function updateFile(array &$files, int $index, array $file) {
		if (!isset($files["size"])) {
			echo "Warning: got a bogus file array!";
			var_dump($files);
			return;
		}
		$multiple = is_array($files["size"]);
		if (!$multiple && $index !== 0) {
			echo "Warning: got index $index for a non-multiple file array!";
			var_dump($files);
			return;
		}
		if ($multiple && $index > count($files["size"]) - 1) {
			echo "Warning: key $index out of range in file array!";
			var_dump($files);
			return;
		}
		if (!$multiple) {
			$files = $file;
			return;
		}
		foreach ($file as $key => $value) {
			$files[$key][$index] = $value;
		}
	}

	/**
	 * @param array<array> $fileArray A PHP-style file array like $_FILES
	 * @return array<AttachmentInfo> Files to attach
	 */
	#[Pure] private function collectAttachments(array $fileArray): array {
		$attachments = [];
		foreach ($fileArray as $metadata) {
			$files = $this->transformFileArray($metadata);
			foreach ($files as $file) {
				if ($file["attached"] ?? false) {
					$attachments[] = new AttachmentInfo($file["tmp_name"], $file["name"], $file["type"]);
				}
			}
		}
		return $attachments;
	}

	/**
	 * Applies data-value, data-value-config, data-href, and data-href-config attributes to a DOM tree.
	 * @param DOMElement|DOMDocument &$root The root element in which to apply the values
	 * @param array $data Values to use for elements with the data-value or data-href attribute
	 * @param array $values Values to use for elements with the data-value-config or data-href-config attribute
	 * @param array $files Uploaded file metadata (in $_FILES format)
	 */
	private function applyDataValues(DOMElement|DOMDocument $root, array $data, array $values, array $files): void {
		foreach (DOMHelpers::collectElements($root, "*", DOMHelpers::has("data-value-config")) as $element) {
			/** @var $element DOMElement */
			if (isset($values[$element->getAttribute("data-value-config")])) {
				$element->nodeValue = htmlspecialchars(strval($values[$element->getAttribute("data-value-config")]));
				$element->removeAttribute("data-value-config");
			}
		}
		foreach (DOMHelpers::collectElements($root, "*", DOMHelpers::has("data-value")) as $element) {
			/** @var $element DOMElement */
			if (isset($data[$element->getAttribute("data-value")])) {
				$value = $data[$element->getAttribute("data-value")];
				if (isset($value["size"])) {
					$this->applyFileTransformation($element, $value);
				} else {
					$element->nodeValue = htmlspecialchars(strval($value));
				}
				$element->removeAttribute("data-value");
			} else if (isset($files[$element->getAttribute("data-value")])) {
				$this->applyFileTransformation($element, $files[$element->getAttribute("data-value")] ?? null);
				$element->removeAttribute("data-value");
			}
		}
		foreach (DOMHelpers::collectElements($root, "*", DOMHelpers::has("data-href-config")) as $element) {
			/** @var $element DOMElement */
			if (isset($values[$element->getAttribute("data-href-config")])) {
				$element->setAttribute("href", strval($values[$element->getAttribute("data-href-config")]));
			}
		}
		foreach (DOMHelpers::collectElements($root, "*", DOMHelpers::has("data-href")) as $element) {
			/** @var $element DOMElement */
			if (isset($data[$element->getAttribute("data-href")])) {
				$value = $data[$element->getAttribute("data-href")];
				if (isset($value["size"])) {
					$this->applyFileTransformation($element, $value, true);
				} else {
					$element->setAttribute("href", strval($value));
				}
			} else if (isset($files[$element->getAttribute("data-href")])) {
				$this->applyFileTransformation($element, $files[$element->getAttribute("data-href")] ?? null);
			}
		}
	}

	/**
	 * Applies a file transformation to an element.
	 * @param DOMElement &$element The element whose inner HTML should be the transformed file.
	 * @param array|null $file File metadata
	 * @param bool $href Apply to the href attribute instead of the inner HTML.
	 */
	function applyFileTransformation(DOMElement $element, ?array $file, bool $href = false): void {
		if ($file === null) {
			echo "Warning: got null file to apply transformation";
			var_dump($element);
			return;
		}
		$transformer = $this->formConfig->fileTransformers[$element->getAttribute("data-file-transformer") ?:
				array_key_first($this->formConfig->fileTransformers)];
		$value = $transformer($file);
		if ($href) {
			$element->setAttribute("href", $value);
			return;
		}
		while ($element->hasChildNodes()) {
			$element->removeChild($element->firstChild);
		}
		$fragment = $element->ownerDocument->createDocumentFragment();
		$fragment->appendXML($value);
		$element->appendChild($fragment);
	}

	/**
	 * Send an email.
	 * @param FormEmailConfig $emailConfig
	 * @param RenderedEmail $renderedEmail The rendered email.
	 * @throws \PHPMailer\PHPMailer\Exception
	 */
	function sendEmail(FormEmailConfig $emailConfig, RenderedEmail $renderedEmail): void {
		$emailConfig->send($renderedEmail);
	}
}

