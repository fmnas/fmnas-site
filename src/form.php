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

/**
 * Include this file at the top of a form page, then set values of $formConfig.
 *
 * The <form> element will be replaced with an <article> element.
 * All <script> elements will be removed unless they have an explicit falsy data-remove attribute.
 *
 * Within the <form> element:
 * All <input> and <select> elements will be replaced with <span> elements, except:
 *   input[type="button"], input[type="submit"], input[type="reset"], input[type="password"], input[type="hidden"],
 *   and input[type="image"] will be removed unless they have an explicit falsy data-remove attribute.
 *   See below for special considerations for input[type="file"], input[type="radio"], and input[type="checkbox"].
 * All <textarea> elements will be replaced with <pre> elements.
 * All <fieldset> elements will be replaced with <section> elements containing <h3> headers.
 * All <button> elements will be removed unless they have an explicit falsy data-remove attribute, in which case they
 * will be replaced with <span> elements.
 * All <label> elements will be replaced with span elements. Any inner text will itself be in a span element with a
 * data-type="label-text" attribute as well, and the outer span element will have a data-input-type attribute with the
 * type of the corresponding input element if found.
 *
 * TODO [#73]: Replace datalist elements with ul elements (hidden by default).
 * All <datalist> elements will be removed unless they have an explicit falsy data-remove attribute, in which case they
 * will be replaced with <ul> elements. <option> elements therein will be replaced with <li> elements, and any contained
 * within an <optgroup> element will have a data-optgroup attribute with the optgroup label (the optgroup is removed).
 *
 * All generated elements will have a data-type attribute with the original tag ("form", "input", etc.).
 * For elements generated from an <input> element, the data-input-type attribute will be set with the original type.
 *
 * Elements with a truthy data-remove attribute will be removed from the rendered email.
 * Elements with a truthy data-ignore attribute will not be modified, though they will still be removed if an ancestor
 * has a truthy data-remove.
 *
 * Inputs with a name ending in [] will be removed in the rendered email unless they have an explicit falsy data-remove
 * attribute. To handle such repeated inputs, add an element with a data-foreach attribute.
 * For example:
 *   <ul>
 *   <li><input type="text" name="cats[]" value="Stephen">
 *   <li><input type="text" name="cats[]" value="Jonathan">
 *   <li><input type="text" name="cats[]" value="Mittens">
 *   <li data-foreach="cats">
 *   </ul>
 *
 * data-foreach may also be used with data-as, in which case the value will be accessible through data-value:
 *   <li data-foreach="cats" data-as="cat">Cat named <span data-value="cat"></span>
 *
 * The value can also be applied to an element's href attribute using data-href:
 *   <a data-href="customer_website">Visit the customer website</a>
 * Note that data-value and data-href are mutually exclusive.
 *
 * To add output only used in the rendered email, use the data-hidden attribute. Elements with this attribute will be
 * hidden on the form page by injected CSS:
 *   <p data-hidden>Thank you for submitting the form! A copy of the received data is below.
 *
 * To use values from the form to generate output, use data-value, data-if, data-operator, data-rhs, and data-rhs-value:
 *
 *   <input type="checkbox" name="display_message" value="1">
 *   <p data-if="display_message">This text will be displayed if the checkbox is checked.
 *
 *   <input type="number" name="number_of_horses">
 *   <p data-if="number_of_horses" data-operator="gt" data-rhs="100">Whoa! That's a lot of horses.
 *   <p data-if="number_of_horses" data-operator="lt" data-rhs="0">That is an unreasonable number of horses.
 *
 *   <input type="number" name="number_of_dogs">
 *   <p data-if="number_of_horses" data-rhs-value="number_of_dogs">There are the same number of horses and dogs.
 *
 * Valid values for data-operator are: gt, lt, eq, ge, le, ne
 *
 * To use values from the arrays provided by the callbacks in $formConfig, append -config to the attribute where a form
 * value or plain text value would otherwise be provided:
 *   <p data-if-config="user-email">Thanks for submitting the form!
 *   <p data-if-config="operator-email"><span data-value="name"></span> submitted the following form.
 *   <p data-if-config="user-email" data-operator="eq" data-rhs-config="operator-email">Hey, that's me!
 *
 * To transform the inner HTML of an element after all other processing is complete, add a data-transformer attribute:
 *   <input type="email" name="email" value="example@example.com" data-transformer="email-link">
 * This must correspond to an entry in the $formConfig->transformers associative array, which is a closure that
 * transforms one HTML string into another HTML string.
 * The default $formConfig includes the following transformers:
 *   - email-link: wraps an email address in a mailto link (useful with input[type="email"])
 *   - tel-link: wraps a phone number in a tel link (useful with input[type="tel"])
 *   - link: wraps a URL in a link (useful with input[type="url"])
 * Note that the output of a data transformer must be valid in the text/xml serialization of HTML5 (XHTML5), not just
 * as text/html. In practice, this generally just means you must close tags: <br /> rather than <br>.
 *
 * data-transformer-if, data-transformer-if-config, data-transformer-operator, data-transformer-rhs,
 * data-transformer-rhs-value, and data-transformer-rhs-config may be used to conditionally apply the transformer
 * specified by data-transformer.
 *
 * All elements with the following attributes will be hidden on the form page by injected CSS, unless a falsy value
 * is explicitly given for data-hidden:
 * data-hidden
 * data-foreach
 * data-foreach-config
 * data-if
 * data-if-config
 * data-value
 * data-value-config
 * data-href
 * data-href-config
 *
 * When processing a file input with data-foreach, data-value, etc., the file metadata array is passed through a file
 * transformer to compute the output value. The file transformers are closures in $formConfig->fileTransformers.
 * The default file transformers are:
 *   - name: the filename (default if no data-file-transformer is given)
 *   - size: the file size in bytes
 *   - type: the file MIME type
 *   - path: the path to the file on the server
 *   - dump: a dump of the file metadata
 *   - image: an inline image (unsupported by many mail clients)
 *   - thumbnail: an inline thumbnail with height 64 (unsupported by many mail clients)
 *
 * To replace filenames with a hash of the file, set FormEmailConfig::hashFilenames to HashOptions::NO (default),
 * HashOptions::SAVED_ONLY, HashOptions::EMAIL_ONLY, or HashOptions::YES.
 * If not NO, $metadata["original"] will be the original filename and $metadata["hash"] will be the hash filename.
 * If EMAIL_ONLY or YES, $metadata["name"] will be changed to the hashed name.
 *
 * To output the files to a directory on the server, set FormEmailConfig::fileDir to a directory, or a closure that
 * takes the file metadata array. This will create $metadata["path"] for the file.
 *
 * To prevent the files from being attached to the email, set FormEmailConfig::attachFiles to false, or a closure
 * that takes the file metadata array.
 * If this directory is served by a web server, you may want to create a link to the file. Since the script is unaware
 * of where the directory can be found, you will have to write your own file transformer to do this:
 *   $formConfig->fileTransformers["url"] = function(array $metadata): string {
 *     return "https://example.com/uploads/" . $metadata["name"];
 *   }
 * Note that the output of a file transformer must be valid in the text/xml serialization of HTML5 (XHTML5), not just
 * as text/html. In practice, this generally just means you must close tags: <br /> rather than <br>.
 *
 * The file metadata array has the standard $_FILES fields, plus "input" (the input name) and sometimes "original",
 * "hash", "path", and "attached".
 *
 * You may want to prevent files from being attached only if they exceed a certain size, or have some other
 * characteristic.
 * This can be accomplished by setting FormEmailConfig::attachFiles to a closure:
 *   $formEmailConfig->attachFiles = function(array $metadata): bool {
 *      if ($metadata["size"] > 1048576) {
 *        // Don't attach any files over 1MB.
 *        return false;
 *      }
 *      $remaining_size = 0;
 *      for ($_FILES as $file_input) {
 *        if (is_array($file_input["size"])) {
 *          foreach ($file_input["size"] as $size) {
 *            if ($size < 1048576) {
 *              $remaining_size += $size;
 *            }
 *          }
 *        } else if ($file_input["size"] < 1048576) {
 *          $remaining_size += $file_input["size"];
 *        }
 *      }
 *      // Don't attach any files if the combined size of all files under 1MB is over 5MB.
 *      if ($remaining_size > 5 * 1048576) {
 *        return false;
 *      }
 *      return true;
 *   }
 * The same applies to FormEmailConfig::fileDir. If this returns a truthy string for a file, it is used as the fileDir.
 * FormEmailConfig::hashFilenames may also be a closure that returns a HashOptions value.
 *
 * Note that the attachFiles closure is the last to be calculated, so it has access to the hash and path values
 * if needed. For example, you could attach all files that were not saved to the server with:
 *   $formEmailConfig->attachFiles = function(array $metadata): bool {
 *     return !($file["path"] ?? false);
 *   }
 *
 * To convert files to another type or otherwise transform the files themselves, use a file converter.
 * File converters are closures FormEmailConfig::fileConverter that take a reference to the metadata array.
 * Note that this conversion takes place *before* FormEmailConfig::hashFilenames.
 * By default, this conversion is scoped to a single email. To use the converted files as the input files for subsequent
 * emails, set FormEmailConfig::globalConversion to true.
 * This should update $file["tmp_name"] if a new file is created.
 *
 * To validate files server-side before saving or attaching them, set $formConfig->fileValidator to a closure.
 * Any files that return false will be ignored.
 *   $formConfig->fileValidator = function(array $metadata): bool {
 *     if ($metadata["error"]) {
 *       return false;
 *     }
 *     $finfo = new finfo(FILEINFO_MIME_TYPE);
 *     return str_starts_with($finfo->file($metadata["tmp_name"]), "image/");
 *   }
 * The default fileValidator simply returns !$metadata["error"].
 *
 * For radio buttons and checkboxes, the output span for each button will contain the submitted value and have
 * data-selected="1" or data-selected="0". Additionally, the output span for any associated labels will have the
 * data-selected attribute as well.
 * These conditions should be handled by a stylesheet so the rendered email is coherent. Some suggested CSS rules:
 *  span[data-type="input"][data-input-type="radio"],
 *  span[data-type="input"][data-input-type="checkbox"],
 *  span[data-type="label"][data-input-type="radio"][data-selected="0"] {
 *    display: none;
 *  }
 *  span[data-type="label"][data-input-type="checkbox"][data-selected="0"]::before {
 *    content: "☐ ";
 *  }
 *  span[data-type="label"][data-input-type="checkbox"][data-selected="1"]::before {
 *    content: "☑ ";
 *  }
 *
 * TODO [#69]: Add unit tests for the form processor.
 * TODO [#70]: Split the form processor into a separate repo?
 * @noinspection GrazieInspection
 */

// Intentionally not including common.php here so this can be independent of the rest of the site someday.
require_once __DIR__ . "/../vendor/autoload.php";
use JetBrains\PhpStorm\Pure;
use Masterminds\HTML5;
use PHPMailer\PHPMailer\PHPMailer;

$pwd = getcwd();

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

	public string $smtpHost;
	public string $smtpSecurity;
	public int $smtpPort;
	public bool $smtpAuth;
	public string $smtpUser;
	public string $smtpPassword;
}

enum HashOptions {
	case NO;
	case SAVED_ONLY;
	case EMAIL_ONLY;
	case YES;
}

enum HTTPMethod {
	case POST;
	case GET;
	case EITHER;
}

/**
 * The configuration for a single email sent after form processing.
 */
class FormEmailConfig {
	/**
	 * The emails for the Reply-To header.
	 * In most cases, this iterable should be an array.
	 * If it doesn't generate any values, the Reply-To header will be omitted.
	 * @var iterable<EmailAddress>
	 */
	public iterable $replyTo;
	/**
	 * The emails for the Cc header.
	 * In most cases, this iterable should be an array.
	 * If it doesn't generate any values, the Cc header will be omitted.
	 * @var iterable<EmailAddress>
	 */
	public iterable $cc;
	/**
	 * The emails for the Bcc header.
	 * In most cases, this iterable should be an array.
	 * If it doesn't generate any values, the Bcc header will be omitted.
	 * @var iterable<EmailAddress>
	 */
	public iterable $bcc;

	/**
	 * If non-empty, a directory on the server to which to output uploaded files.
	 * Can also be a closure that takes the file metadata array.
	 * @param array file metadata array
	 * @return string
	 */
	public string|Closure $fileDir;

	/**
	 * Whether to attach uploaded files to the email.
	 * Can also be a closure that takes the file metadata array.
	 * @param array file metadata array
	 * @return bool
	 */
	public bool|Closure $attachFiles;

	/**
	 * Uploaded filenames can be replaced with a hash of the file.
	 * (This is desirable for security if $fileDir is a publicly accessible directory.)
	 * Can also be a closure that takes the file metadata array
	 * @param array file metadata array
	 * @return HashOptions
	 */
	public HashOptions|Closure $hashFilenames;

	/**
	 * Converter applied to files before hashFilenames.
	 * @param array &$metadata file metadata array
	 * @return void
	 */
	public ?Closure $fileConverter;

	/**
	 * Whether to apply $fileConverter to $_FILES instead of $files.
	 */
	public bool $globalConversion;

	/**
	 * If non-empty, a path on the server to which to save the rendered form as an HTML file in lieu of
	 * emailing it.
	 */
	public string $saveFile;

	/**
	 * FormEmailConfig constructor.
	 * @param EmailAddress|null $from The email for the From header, or null to dump HTML rather than emailing.
	 * @param iterable<EmailAddress> $to The emails for the To header.
	 * @param string $subject The value for the Subject header.
	 * @param array|null $values An optional array of arbitrary data accessible
	 * when rendering the email. If an element in the form has a data-value
	 * attribute, its value will be used as a key when accessing this array.
	 * For example, <input type="hidden" data-value="foo"> will be rendered as
	 * <span>{{$values['foo']}}</span>
	 */
	public function __construct(public ?EmailAddress $from, public iterable $to, public string $subject,
			public ?array $values = []) {
		$this->replyTo = [];
		$this->cc = [];
		$this->bcc = [];
		$this->fileDir = "";
		$this->attachFiles = true;
		$this->hashFilenames = HashOptions::NO;
		$this->fileConverter = null;
		$this->globalConversion = false;
		$this->saveFile = "";
	}
}

class EmailAddress {
	public function __construct(public string $address, public ?string $name = null) {
	}
}

/**
 * A FormError will be created and passed to $formConfig->error in the
 * event of an error during submission.
 */
class FormException extends Exception {
	/**
	 * The form data array ($_POST or $_GET).
	 */
	public array $formData;

	#[Pure] public function __construct(Exception $e, ?array $data = []) {
		parent::__construct($e->getMessage(), $e->getCode(), $e);
		$this->formData = $data ?? [];
	}
}

/**
 * This function is called after the form page is rendered.
 * If the form data has already been submitted, the rendered HTML is parsed
 * and used to generate and send the email.
 * Otherwise, the rendered HTML is output to the browser.
 */
function collectForm(): void {
	global $formConfig;

	// Get the server-rendered form HTML from the page output.
	$html = ob_get_clean();

	// Get the submitted POST or GET data.
	$receivedData = match ($formConfig->method) {
		HTTPMethod::GET => $_GET,
		HTTPMethod::POST => $_POST,
		HTTPMethod::EITHER => $_POST ?: $_GET,
	};

	if ($receivedData || $_FILES) {
		try {
			processForm($receivedData, $html);
			($formConfig->confirm)($receivedData);
		} catch (Exception $e) {
			($formConfig->handler)(new FormException($e, $receivedData));
		}
	} else {
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
}

/**
 * Send emails containing the submitted form data.
 * @param array $data Raw form data ($_GET or $_POST).
 * @param string $html The server-rendered HTML of the empty form.
 * @throws \PHPMailer\PHPMailer\Exception
 * @throws DOMException
 */
function processForm(array $data, string $html): void {
	global $formConfig;
	$data["_FORM_DEDUPLICATION_METADATA_"] = date("Ymd") . (@md5_file(__FILE__) ?: "") . md5($html);

	validateFiles();

	foreach (($formConfig->emails)($data) as $emailConfig) {
		/** @var $emailConfig FormEmailConfig */
		$renderedForm = renderForm($data, $html, $emailConfig);

		sendEmail($emailConfig, $renderedForm);
	}
}

/**
 * Send an email.
 * @param FormEmailConfig $emailConfig
 * @param RenderedEmail $renderedEmail The rendered email.
 * @throws \PHPMailer\PHPMailer\Exception
 */
function sendEmail(FormEmailConfig $emailConfig, RenderedEmail $renderedEmail): void {
	global $formConfig;

	$emailBody = $renderedEmail->html;
	$attachments = $renderedEmail->attachments;

	if ($emailConfig->from === null || $emailConfig->saveFile) {
		$html = $emailBody;
		foreach ($attachments as $attachment) {
			$html .= "\n<!-- attach $attachment->path as $attachment->filename with type $attachment->type -->";
		}
		if ($emailConfig->saveFile) {
			file_put_contents($emailConfig->saveFile, $html);
			return;
		}
		echo $html;
		return;
	}

	$mailer = new PHPMailer(true);
	$mailer->IsSMTP();
	$mailer->Host = $formConfig->smtpHost;
	$mailer->Port = $formConfig->smtpPort;
	$mailer->SMTPAuth = $formConfig->smtpAuth;
	$mailer->SMTPSecure = $formConfig->smtpSecurity;
	$mailer->Username = $formConfig->smtpUser;
	$mailer->Password = $formConfig->smtpPassword;
	$mailer->From = $emailConfig->from->address;
	$mailer->CharSet = 'UTF-8';
	$mailer->Encoding = 'base64';
	if ($emailConfig->from->name) {
		$mailer->FromName = $emailConfig->from->name;
	}
	foreach ($emailConfig->to as $to) {
		/** @var $to EmailAddress */
		$mailer->AddAddress($to->address, $to->name ?: '');
	}
	foreach ($emailConfig->replyTo as $replyTo) {
		/** @var $replyTo EmailAddress */
		$mailer->AddReplyTo($replyTo->address, $replyTo->name ?: '');
	}
	foreach ($emailConfig->cc as $cc) {
		/** @var $cc EmailAddress */
		$mailer->AddCc($cc->address, $cc->name ?: '');
	}
	foreach ($emailConfig->bcc as $bcc) {
		/** @var $bcc EmailAddress */
		$mailer->AddBcc($bcc->address, $bcc->name ?: '');
	}
	foreach ($attachments as $attachment) {
		/** @var $attachment AttachmentInfo */
		$mailer->addAttachment($attachment->path, $attachment->filename,
				startsWith($attachment->type, "text/") ? "quoted-printable" : "base64", $attachment->type);
	}
	$mailer->IsHTML(true);
	$mailer->Subject = $emailConfig->subject;
	$mailer->Body = $emailBody;
	$mailer->Send();
}

/**
 * Copy all attributes from one DOMElement to another.
 * @param DOMElement $from The element that has the attributes.
 * @param DOMElement $to The element that wants the attributes.
 * @param string ...$exclude A list of attributes not to copy.
 * @return void
 */
function copyAttributes(DOMElement $from, DOMElement $to, string ...$exclude): void {
	if ($from->hasAttributes()) {
		foreach ($from->attributes as $attribute) {
			if (!in_array($attribute->nodeName, $exclude)) {
				$to->setAttribute($attribute->nodeName, $attribute->nodeValue);
			}
		}
	}
}

/**
 * Move all children from one DOMElement to another.
 * @param DOMElement $from The element that has the children.
 * @param DOMElement $to The element that wants the children.
 * @return void
 */
function moveChildren(DOMElement $from, DOMElement $to): void {
	while ($from->firstChild) {
		$to->appendChild($from->firstChild);
	}
}

/**
 * Check truthiness of a string. Defined the same as in PHP, except "false" is falsy.
 * TODO [#72]: Make "" truthy (doesn't seem to work?)
 * @param string $str A string
 * @return bool Whether the string is truthy.
 */
#[Pure] function checkString(string $str): bool {
	return $str && $str !== "false";
}

/**
 * Build an array of elements within a DOMElement or DOMDocument with the given tag that match the filter.
 * By default, all tags are matched and no filter is applied.
 * Elements with a truthy data-ignore are never returned.
 * Using getElementsByTagName directly in a foreach loop means you can't remove elements, since this messes up the
 * internal iterator. This way you can remove elements while iterating.
 * @param DOMElement|DOMDocument $root The root element to search.
 * @param string $tag The tag to match.
 * @param Closure|null $filter A filter (DOMElement -> bool) to apply to elements before adding them to the array.
 * @return array An array containing elements.
 * @noinspection PhpUnusedParameterInspection
 */
function collectElements(DOMElement|DOMDocument $root, string $tag = "*", ?Closure $filter = null): array {
	$filter ??= function(DOMElement $element): bool {
		return true;
	};
	$elements = [];
	foreach ($root->getElementsByTagName($tag) as $element) {
		/** @var $element DOMElement */
		if ($filter($element) && !checkString($element->getAttribute("data-ignore"))) {
			$elements[] = $element;
		}
	}
	if ($root instanceof DOMElement &&
			($root->tagName === "form" || ($root->tagName === "article" && $root->getAttribute("data-type") === "form")) &&
			$root->getAttribute("id")) {
		$elementsOutsideForm =
				collectElements($root->ownerDocument, $tag, function(DOMElement $element) use ($root, $filter): bool {
					return $element->getAttribute("form") === $root->getAttribute("id") && $filter($element);
				});
		array_push($elements, ...$elementsOutsideForm);
	}
	return $elements;
}

/**
 * Generates a closure that returns true if the given element has any of the given attributes.
 * @param string ...$attributes Attributes to check
 * @return Closure to be passed to collectElements
 */
#[Pure] function has(string ...$attributes): Closure {
	return function(DOMElement $element) use ($attributes): bool {
		foreach ($attributes as $attribute) {
			if ($element->hasAttribute($attribute)) {
				return true;
			}
		}
		return false;
	};
}

/**
 * Generates a closure that returns true if the given attribute on the given element is truthy.
 * @param string $attribute An attribute to check
 * @return Closure to be passed to collectElements
 */
#[Pure] function truthy(string $attribute): Closure {
	return function(DOMElement $element) use ($attribute): bool {
		return checkString($element->getAttribute($attribute));
	};
}

/**
 * Generates a closure that returns true if the given attribute on the given element equals the given value.
 * @param string $attribute An attribute to check
 * @param string $value A value against which to compare the attribute
 * @return Closure to be passed to collectElements
 */
#[Pure] function attr(string $attribute, string $value): Closure {
	return function(DOMElement $element) use ($attribute, $value): bool {
		return $element->getAttribute($attribute) === $value;
	};
}

/**
 * Applies a file transformation to an element.
 * @param DOMElement &$element The element whose inner HTML should be the transformed file.
 * @param array|null $file File metadata
 * @param bool $href Apply to the href attribute instead of the inner HTML.
 */
function applyFileTransformation(DOMElement $element, ?array $file, bool $href = false): void {
	global $formConfig;
	if ($file === null) {
		echo "Warning: got null file to apply transformation";
		var_dump($element);
		return;
	}
	$transformer = $formConfig->fileTransformers[$element->getAttribute("data-file-transformer") ?:
			array_key_first($formConfig->fileTransformers)];
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
 * Applies data-value, data-value-config, data-href, and data-href-config attributes to a DOM tree.
 * @param DOMElement|DOMDocument &$root The root element in which to apply the values
 * @param array $data Values to use for elements with the data-value or data-href attribute
 * @param array $values Values to use for elements with the data-value-config or data-href-config attribute
 * @param array $files Uploaded file metadata (in $_FILES format)
 */
function applyDataValues(DOMElement|DOMDocument $root, array $data, array $values, array $files): void {
	foreach (collectElements($root, "*", has("data-value-config")) as $element) {
		/** @var $element DOMElement */
		if (isset($values[$element->getAttribute("data-value-config")])) {
			$element->nodeValue = htmlspecialchars(strval($values[$element->getAttribute("data-value-config")]));
			$element->removeAttribute("data-value-config");
		}
	}
	foreach (collectElements($root, "*", has("data-value")) as $element) {
		/** @var $element DOMElement */
		if (isset($data[$element->getAttribute("data-value")])) {
			$value = $data[$element->getAttribute("data-value")];
			if (isset($value["size"])) {
				applyFileTransformation($element, $value);
			} else {
				$element->nodeValue = htmlspecialchars(strval($value));
			}
			$element->removeAttribute("data-value");
		} else if (isset($files[$element->getAttribute("data-value")])) {
			applyFileTransformation($element, $files[$element->getAttribute("data-value")] ?? null);
			$element->removeAttribute("data-value");
		}
	}
	foreach (collectElements($root, "*", has("data-href-config")) as $element) {
		/** @var $element DOMElement */
		if (isset($values[$element->getAttribute("data-href-config")])) {
			$element->setAttribute("href", strval($values[$element->getAttribute("data-href-config")]));
		}
	}
	foreach (collectElements($root, "*", has("data-href")) as $element) {
		/** @var $element DOMElement */
		if (isset($data[$element->getAttribute("data-href")])) {
			$value = $data[$element->getAttribute("data-href")];
			if (isset($value["size"])) {
				applyFileTransformation($element, $value, true);
			} else {
				$element->setAttribute("href", strval($value));
			}
		} else if (isset($files[$element->getAttribute("data-href")])) {
			applyFileTransformation($element, $files[$element->getAttribute("data-href")] ?? null);
		}
	}
}

/**
 * Convert a file array with multiple files to an array of file arrays.
 * @param array $metadata A PHP-style file metadata array (like $_FILES["something"])
 * @return array A sane file metadata array
 */
#[Pure] function transformFileArray(array $metadata): array {
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
 * Propagate changes to a file from a transformed files array back to the original files array.
 * @param array $files A PHP-style file metadata array like ($_FILES["something"])
 * @param int $index The index of the file to update
 * @param array $file The file (in transformFileArray) to put in $files
 * @return void
 */
function updateFile(array &$files, int $index, array $file) {
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
 * Apply the fileConverter, hashFilenames, and fileDir conversions to files, and add "input" and "attached" to files.
 * @param FormEmailConfig $emailConfig
 * @return array The updated file metadata array
 */
function applyFileConversions(FormEmailConfig $emailConfig): array {
	if ($emailConfig->globalConversion) {
		$files = &$_FILES;
	} else {
		$files = $_FILES;
	}

	foreach ($files as $inputName => &$fileArray) {
		$transformed = transformFileArray($fileArray);
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

			updateFile($fileArray, $index, $file);
		}
	}

	// If $files is a reference, this still returns a copy because PHP. https://3v4l.org/T5cKC
	return $files;
}

/**
 * Remove invalid files from $_FILES.
 */
function validateFiles(): void {
	global $formConfig;
	$removeInputs = [];
	foreach ($_FILES as $inputName => &$fileArray) {
		if (!isset($fileArray["size"])) {
			echo "Warning: got a bogus file array!";
			var_dump($fileArray);
			continue;
		}
		$multiple = is_array($fileArray["size"]);
		$transformed = transformFileArray($fileArray);
		$invalidFiles = [];
		foreach ($transformed as $index => $file) {
			if (!($formConfig->fileValidator)($file)) {
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

class AttachmentInfo {
	public function __construct(public string $path, public string $filename, public string $type) {
	}
}

/**
 * @param array<array> $fileArray A PHP-style file array like $_FILES
 * @return array<AttachmentInfo> Files to attach
 */
#[Pure] function collectAttachments(array $fileArray): array {
	$attachments = [];
	foreach ($fileArray as $metadata) {
		$files = transformFileArray($metadata);
		foreach ($files as $file) {
			if ($file["attached"] ?? false) {
				$attachments[] = new AttachmentInfo($file["tmp_name"], $file["name"], $file["type"]);
			}
		}
	}
	return $attachments;
}

class RenderedEmail {
	/**
	 * @param string $html
	 * @param array<AttachmentInfo> $attachments
	 */
	public function __construct(public string $html, public array $attachments) {
	}
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
function renderForm(array $data, string $html, FormEmailConfig $emailConfig): RenderedEmail {
	global $formConfig, $pwd;

	$files = applyFileConversions($emailConfig);
	$attachments = collectAttachments($files);

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
	moveChildren($originalForm, $form);
	copyAttributes($originalForm, $form, "method", "enctype");
	$form->setAttribute("data-type", "form");
	$originalForm->parentNode?->replaceChild($form, $originalForm);

	// Replace input elements with span elements.
	foreach (collectElements($form, "input") as $input) {
		/** @var $input DOMElement */
		$span = $dom->createElement("span");
		$span->setAttribute("data-type", "input");
		$inputName = $input->getAttribute("name");
		copyAttributes($input, $span, "value", "required", "type", "accept", "capture", "multiple", "form", "checked",
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
	foreach (collectElements($form, "select") as $select) {
		/** @var $select DOMElement */
		$span = $dom->createElement("span");
		$span->setAttribute("data-type", "select");
		$inputName = $select->getAttribute("name");
		copyAttributes($select, $span, "value", "required", "form", "name", "disabled");
		$selected = $data[$inputName] ?? "";
		foreach (collectElements($select, "option") as $option) {
			/** @var $option DOMElement */
			if (($option->getAttribute("value") ?: $option->nodeValue) === $selected) {
				$span->nodeValue = htmlspecialchars(strval($option->nodeValue ?: $selected));
				break;
			}
		}
		$select->parentNode?->replaceChild($span, $select);
	}

	// Replace textarea elements with pre elements.
	foreach (collectElements($form, "textarea") as $textarea) {
		/** @var $textarea DOMElement */
		$pre = $dom->createElement("pre");
		$pre->setAttribute("data-type", "textarea");
		$inputName = $textarea->getAttribute("name");
		copyAttributes($textarea, $pre, "value", "required", "form", "name", "disabled");
		$pre->nodeValue = htmlspecialchars(strval($data[$inputName] ?? $textarea->nodeValue));
		$textarea->parentNode?->replaceChild($pre, $textarea);
	}

	// Replace fieldset elements with section elements with h3 headers.
	foreach (collectElements($form, "fieldset") as $fieldset) {
		/** @var $fieldset DOMElement */

		// Ideally a fieldset should contain exactly one legend, but you never know.
		$legends = collectElements($fieldset, "legend");
		if (count($legends)) {
			/** @var $legend DOMElement */
			$legend = $legends[0];
			$h3 = $dom->createElement("h3");
			copyAttributes($legend, $h3);
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
		copyAttributes($fieldset, $section, "form");
		$section->setAttribute("data-type", "fieldset");
		moveChildren($fieldset, $section);
		$fieldset->parentNode?->replaceChild($section, $fieldset);
	}

	// Replace label elements with span elements.
	foreach (collectElements($dom, "label") as $label) {
		/** @var $label DOMElement */
		$type = null;
		$selected = null;
		// Look for input elements within the label to determine type and selected.
		if ($childInput = collectElements($label, "*", has("data-type"))[0] ?? false) {
			$elementType = $childInput->getAttribute("data-type");
			$type = $elementType === "input" ? $childInput->getAttribute("data-input-type") : $elementType;
			$selected = $childInput->getAttribute("data-selected");
		}
		if (!$type && $label->getAttribute("for")) {
			$input = collectElements($dom, "*", attr("id", $label->getAttribute("for")))[0] ?? null;
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
		copyAttributes($label, $span, "for");
		moveChildren($label, $span);
		$label->replaceWith($span);
	}

	// Replace button elements with span elements (hidden by default).
	foreach (collectElements($form, "button") as $button) {
		/** @var $button DOMElement */
		$span = $dom->createElement("span");
		$span->setAttribute("data-type", "button");
		copyAttributes($button, $span, "value", "required", "type", "name", "formaction", "disabled");
		if (!$span->hasAttribute("data-remove")) {
			$span->setAttribute("data-remove", "1");
		}
		$button->parentNode?->replaceChild($span, $button);
	}

	// Mark all script elements with data-remove.
	foreach (collectElements($dom, "script") as $script) {
		/** @var $script DOMElement */
		if (!$script->hasAttribute("data-remove")) {
			$script->setAttribute("data-remove", "1");
		}
	}

	// Process data-foreach elements.
	foreach (collectElements($dom, "*", has("data-foreach", "data-foreach-config")) as $element) {
		/** @var $element DOMElement */

		$arr = $data[$element->getAttribute("data-foreach")] ??
				$values[$element->getAttribute("data-foreach-config")] ?? null;
		$fileInput = false;
		if ($arr === null) {
			$fileInput = true;
			$arr = transformFileArray($files[$element->getAttribute("data-foreach")] ?? []);
		}
		if (!is_array($arr)) {
			$arr = [$arr];
		}

		$newNodes = [];

		foreach ($arr as $value) {
			$clone = $element->cloneNode(true);
			if ($element->hasAttribute("data-as")) {
				applyDataValues($clone, [...$data, $element->getAttribute("data-as") => $value], $values, $files);
			} else {
				if ($fileInput) {
					$transformer =
							$formConfig->fileTransformers[$element->getAttribute("data-file-transformer") ?: "name"];
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
	foreach (collectElements($dom, "*", has("data-if", "data-if-config")) as $element) {
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
	foreach (collectElements($dom, "*", truthy("data-remove")) as $element) {
		/** @var $element DOMElement */
		$element->parentNode?->removeChild($element);
	}

	// Merge linked style sheets into the output HTML.
	$stylesToInject = [];
	foreach (collectElements($dom, "link", attr("rel", "stylesheet")) as $link) {
		/** @var $link DOMElement */
		$href = $link->getAttribute("href");
		$url = parse_url($href);
		// TODO [#74]: Consider solutions for $url["query"] === $_SERVER["HTTP_HOST"]
		// TODO [#75]: Consider solutions for startsWith($url["path"], "/")
		if (!isset($url["host"]) && !startsWith($url["path"], "/")) {
			// Relative path
			ob_start();
			if (isset($url["query"])) {
				parse_str($url["query"], $_GET);
			}
			$file = $pwd . "/" . $url["path"];
			if (!is_file($file)) {
				// Ignore this one
				continue;
			}
			include $file;
			$styles = ob_get_clean();
		} else {
			// Absolute path
			if (!ini_get('allow_url_fopen')) {
				// Can't get the file :(
				continue;
			}
			$path = $url["scheme"] ?? (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http");
			$path .= "://";
			$path .= $url["host"] ?? $_SERVER["HTTP_HOST"];
			if (isset($url["port"])) {
				$path .= ":" . $url["port"];
			}
			$path .= $url["path"];
			if (isset($url["query"])) {
				$path .= "?" . $url["query"];
			}
			$styles = file_get_contents($path);
		}
		if ($styles === false) {
			// Failed to get styles; delegate responsibility to the email client.
			continue;
		}
		$style = $dom->createElement("style");
		$style->setAttribute("type", "text/css");
		$style->setAttribute("data-type", "link");
		copyAttributes($link, $style, "rel", "href");
		$locator = "/* INJECT STYLE HERE: " . sha1($styles) . " */";
		$stylesToInject[$locator] = $styles;
		$style->nodeValue = $locator; // Can't inject right now because we would end up with HTML entities, etc.
		$link->parentNode?->replaceChild($style, $link);
	}

	applyDataValues($dom, $data, $values, $files);

	// Apply data transformations.
	foreach (collectElements($dom, "*", has("data-transformer")) as $element) {
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
		if (isset($formConfig->transformers[$transformer])) {
			if ($element->hasAttribute("data-href") || $element->hasAttribute("data-href-config")) {
				$element->setAttribute("href", $formConfig->transformers[$transformer]($element->getAttribute("href")));
			} else {
				$innerHTML = "";
				while ($element->hasChildNodes()) {
					$innerHTML .= $dom->saveHTML($element->firstChild);
					$element->removeChild($element->firstChild);
				}
				$transformed = $formConfig->transformers[$transformer]($innerHTML);
				$fragment = $element->ownerDocument->createDocumentFragment();
				$fragment->appendXML($transformed);
				$element->appendChild($fragment);
			}
		} else {
			echo "Warning: transformer $transformer not found; leaving value untransformed.";
		}
	}

	return new RenderedEmail(str_replace(array_keys($stylesToInject), array_values($stylesToInject), $dom->saveHTML()),
			$attachments);
}

/** @noinspection PhpObjectFieldsAreOnlyWrittenInspection
 * Seems PHPStorm isn't smart enough to deal with this being read only from a registered shutdown function.
 * (Can't really blame it)
 */
$formConfig = new FormConfig();
// Default values for $formConfig.
$formConfig->confirm = function(array $formData): void {
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
$formConfig->handler = function(FormException $e): void {
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

// Default values that should be overridden by setting $formConfig after including this file.
$formConfig->emails = function(array $formData): array {
	$email = new EmailAddress('webmaster@' . $_SERVER['HTTP_HOST']);
	$config = new FormEmailConfig($email, [$email], 'Form Data');
	return [$config];
};
$formConfig->smtpHost = 'localhost';
$formConfig->smtpAuth = true;
$formConfig->smtpSecurity = '';
$formConfig->smtpPort = 25;
$formConfig->smtpUser = 'root';
$formConfig->smtpPassword = '';
$formConfig->method = HTTPMethod::EITHER;
$formConfig->transformers = [
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
$formConfig->fileTransformers = [
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
$formConfig->fileValidator = function(array $metadata): bool {
	return !$metadata["error"];
};

ob_start();
register_shutdown_function('collectForm');
