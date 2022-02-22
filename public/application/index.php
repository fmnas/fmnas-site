<?php /** @noinspection PhpUnusedParameterInspection */
# Copyright 2022 Google LLC
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

require_once "../../src/common.php";

use fmnas\Form\AttachmentInfo;
use fmnas\Form\EmailAddress;
use fmnas\Form\FormConfig;
use fmnas\Form\FormEmailConfig;
use fmnas\Form\FormException;
use fmnas\Form\HashOptions;
use fmnas\Form\HTTPMethod;
use fmnas\Form\FormProcessor;
use fmnas\Form\RenderedEmail;
use fmnas\Form\SMTPConfig;

require_once "$src/db.php";
require_once "$src/resize.php";
require_once "$src/minify.php";
require_once "$src/pdf.php";
require_once "$t/header.php";
require_once "$t/application_response.php";
require_once "$t/footer.php";

// Useful for debugging.
// TODO [$62155c5f3490c2000996ad45]: Check that $DEDUPLICATE is true when merging.
$DEDUPLICATE = false;

ini_set('memory_limit', '2048M');
setlocale(LC_ALL, 'en_US.UTF-8');
set_time_limit(3600);
$formConfig = new FormConfig();
$formConfig->method = HTTPMethod::POST;
$db ??= new Database();
$cwd = getcwd();

$smtpConfig = new SMTPConfig(
		host: Config::$smtp_host,
		security: Config::$smtp_security,
		port: Config::$smtp_port,
		auth: Config::$smtp_auth,
		user: Config::$smtp_username,
		password: Config::$smtp_password
);

$formConfig->returnEarly = true;
$formConfig->received = function(array $formData) use ($cwd): void {
	?>
	<!DOCTYPE html>
	<html lang="en-US">
	<title>Adoption Application - <?=_G_longname()?></title>
	<meta charset="UTF-8">
	<meta name="robots" content="noindex,nofollow">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<?php
	style();
	style('thanks', true);
	pageHeader();
	?>
	<article>
		<h2>Adoption Application</h2>
		<p>Thank you! We have received your application and you will hear back from us soon.
		<p><a href="/">Return to the shelter homepage</a>
	</article>
	</html>
	<?php
	file_put_contents("$cwd/received/" . microtime(true) . ".serialized", serialize($formData));
};

$formConfig->handler = function(FormException $e) use ($smtpConfig): void {
	log_err(print_r($e, true));
	http_response_code(500);
	?>
	<!DOCTYPE html>
	<html lang="en-US">
	<title>Adoption Application - <?=_G_longname()?></title>
	<meta charset="UTF-8">
	<meta name="robots" content="noindex,nofollow">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<?php
	style();
	style('thanks', true);
	emailLinks();
	pageHeader();
	?>
	<article>
		<article>
			<h2>Error <?=$e->getCode() ?: 500?></h2>
			<p>Something went wrong submitting the form: <?=$e->getMessage()?>
			<p><img src="//http.cat/500" alt="">
			<p>Please contact Sean at <a data-email="sean"></a> with the following information:
			<pre><?php
				var_dump($e);
				?>
	    </pre>
			<p><a href="/">Return to the shelter homepage</a>
		</article>
	</html>
	<?php
	// Attempt to email the PHP context to admin@ so we can fix it.
	$email = new FormEmailConfig(
			new EmailAddress("admin@" . _G_public_domain()),
			[new EmailAddress("admin@" . _G_public_domain())],
			"Application Error Context",
			$smtpConfig);
	@$email->send(new RenderedEmail(
			'<pre>' . print_r(get_defined_vars(), true) . '</pre>',
			[]));
};

$formConfig->emails = function(array $formData) use ($DEDUPLICATE, $smtpConfig, $cwd): array {
	$shelterEmail = new EmailAddress(_G_default_email_user() . '@' . _G_public_domain(), _G_shortname());
	$applicantEmail = new EmailAddress(trim($formData['AEmail']), trim($formData['AName']));
	$applicantFakeEmail = new EmailAddress('noreply@' . _G_public_domain(), trim($formData['AName']));

	$hash = sha1(print_r($formData, true));
	$path = "https://" . _G_public_domain() . "/application/received/$hash.html";

	$outside_warn = $formData['will_live'] === 'inside' && $formData['will_live_tracker'];
	$outside_message = 'Warning: This applicant checked then unchecked "pet will live outside."';

	$save = new FormEmailConfig(
			null,
			[],
			'',
			$smtpConfig,
			['main' => true, 'path' => $path, 'thumbnails' => true, 'minhead' => true,
					'outside_warn' => $outside_warn, 'outside_message' => $outside_message,]
	);
	$save->saveFile = "$cwd/received/$hash.html";
	$save->fileDir = function(array $file) use ($cwd): string {
		return "$cwd/received";
	};
	$save->hashFilenames = HashOptions::SAVED_ONLY;
	$save->filesConverter = function(array &$files) use (&$total_size): void {
		$total_size = 0;
		$filespecs = [];
		$count = 0;
		$heic_count = 0;
		foreach ($files as $index => &$file) {
			if (!startsWith($file["type"], "image/")) {
				$filespecs[$index] = null;
				continue;
			}
			$filespec = new FileSpec();
			$filespec->source = $file["tmp_name"];
			$filespec->target = $file["tmp_name"] . ".jpg";
			$filespec->height = 8640;
			$filespecs[$index] = $filespec;
			if (startsWith($file["type"], "image/hei")) {
				$heic_count++;
			}
			$count++;
		}
		if ($count < 10 && $heic_count < 3) {
			// Probably better to do the resizing locally in this case.
			foreach ($filespecs as $index => $filespec) {
				try {
					resize($filespec->source, $filespec->target, $filespec->height);
					if ($files[$index]["type"] !== "image/jpeg") {
						$files[$index]["type"] = "image/jpeg";
						$files[$index]["name"] .= ".jpg";
					}
					$files[$index]["tmp_name"] = $filespec->target;
					$files[$index]["size"] = filesize($filespec->target);
				} catch (ImageResizeException $e) {
					continue;
				}
			}
		} else {
			$results = resizeMultiple($filespecs);
			foreach ($results as $index => $result) {
				if ($result === true) {
					if ($files[$index]["type"] !== "image/jpeg") {
						$files[$index]["type"] = "image/jpeg";
						$files[$index]["name"] .= ".jpg";
					}
					$files[$index]["tmp_name"] = $filespecs[$index]->target;
					$files[$index]["size"] = filesize($files[$index]["tmp_name"]);
				}
			}
		}
		unset($file);
		foreach ($files as $file) {
			$total_size += $file["size"];
		}
		unset($file);
		foreach ($files as &$file) {
			$file["total_size"] = $total_size; // For use by attachFiles
		}
	};
	$save->globalConversion = true;
	$save->emailTransformation = function(DOMDocument $dom, array &$attachments) use ($cwd): null|string {
		return inlineStyles($dom, $cwd) ?: null;
	};

	$dump = new FormEmailConfig(
			null,
			[],
			'',
			$smtpConfig,
			['main' => true, 'path' => $path, 'weblink' => true,
					'outside_warn' => $outside_warn, 'outside_message' => $outside_message,]
	);

	$primarySubject = 'Adoption Application';
	if (trim($formData['particular_specify'] ?? '') && strlen($formData['particular_specify']) < 20) {
		$primarySubject .= ' for ' . trim($formData['particular_specify']);
	}
	$primarySubject .= ' from ' . trim($formData['AName']);
	if (trim($formData['CName'] ?? '')) {
		$primarySubject .= ' & ' . trim($formData['CName']);
	}

	$primaryEmail = new FormEmailConfig(
			$applicantFakeEmail,
			[$shelterEmail],
			$primarySubject,
			$smtpConfig,
			['main' => true, 'path' => $path, 'weblink' => true,
					'outside_warn' => $outside_warn, 'outside_message' => $outside_message,]
	);
	$primaryEmail->attachFiles = function(array $file): bool {
		return isset($file["total_size"]) && $file["total_size"] < 20 * 1048576;
	};
	$primaryEmail->replyTo = [$applicantEmail];
	$primaryEmail->emailTransformation =
			function(DOMDocument $dom, array &$attachments) use ($cwd, $primarySubject): null|string {
				try {
					// Render a PDF of the email.
					$file = tempnam(sys_get_temp_dir(), "PDF");
					renderPdf($dom, $file, "https://forgetmenotshelter.org/application/", "0.5in");
					// Attach the PDF
					$filename = preg_replace('/[^a-zA-Z0-9& _+-]+/', '-', $primarySubject) . ".pdf";
					$attachments[] = new AttachmentInfo($file, $filename, "application/pdf");
				} catch (PdfException $e) {
					log_err($e->getMessage());
				}

				$inlined = inlineStyles($dom, $cwd);

				try {
					return remoteMinify($inlined, "https://forgetmenotshelter.org/application/");
				} catch (MinifyException) {
					return $inlined;
				}
			};

	$secondaryEmail = new FormEmailConfig(
			$shelterEmail,
			[$applicantEmail],
			'Your ' . _G_shortname() . ' Adoption Application',
			$smtpConfig,
			['main' => false, 'minhead' => true, 'showrentp' => true]
	);
	$secondaryEmail->attachFiles = false;
	if ($formData['CEmail']) {
		$secondaryEmail->cc = [new EmailAddress(trim($formData['CEmail']), trim($formData['CName']))];
	}
	$secondaryEmail->emailTransformation =
			function(DOMDocument $dom, array &$attachments) use ($primarySubject, $cwd): null|string {
				try {
					// Render a PDF of the email.
					$file = tempnam(sys_get_temp_dir(), "PDF");
					renderPdf($dom, $file, "https://forgetmenotshelter.org/application/", "0.5in");
					// Attach the PDF
					$attachments[] = new AttachmentInfo($file, "Adoption Application.pdf", "application/pdf");
					$dom->getElementById('response_injection')?->appendChild($dom->createTextNode(
							'A copy of your application is attached for your records.'
					));
				} catch (Exception $e) {
					log_err($e->getMessage());
					try {
						$dom->getElementById('response_injection')?->remove();
					} catch (Exception) {
						// ignore.
					}
				}

				try {
					if (!($minhead = $dom->getElementById('minimal_header'))) {
						throw new Exception('No minhead found');
					}
					$minhead->remove();
				} catch (Exception $e) {
					log_err($e->getMessage());
				}
				try {
					// dunno why getElementById('application') doesn't work
					foreach ($dom->getElementsByTagName('article') as $article) {
						/** @var $article DOMElement */
						if ($article->getAttribute("id") === "application") {
							$article->remove();
							break;
						}
					}
				} catch (Exception $e) {
					log_err($e->getMessage());
				}

				$inlined = inlineStyles($dom, $cwd);

				try {
					return remoteMinify($inlined, "https://forgetmenotshelter.org/application/");
				} catch (MinifyException) {
					return $inlined;
				}
			};

	if (file_exists($save->saveFile)) {
		echo '<!-- Application not sent - detected duplicate at ' . $save->saveFile . ' -->';
		if ($DEDUPLICATE) {
			return [];
		}
	}

	return [$save, $primaryEmail, $secondaryEmail];
};
$formConfig->fileTransformers["url"] = function(array $metadata): string {
	return "https://" . _G_public_domain() . "/application/received/" . ($metadata["hash"] ?? "UNKNOWN");
};
$formConfig->transformers["mailto"] = function(string $email): string {
	return "mailto:$email";
};

$processor = new FormProcessor($formConfig);
ob_start();
function collect() {
	global $processor;
	$processor->collectForm();
}
register_shutdown_function('collect');

function options(array $opts): string {
	$options = "";
	foreach ($opts as $key => $value) {
		$options .= "<option value=\"$key\" aria-label=\"$value\" title=\"$value\">$key</option>";
	}
	return $options;
}


function addressInput(string $label, string $prefix, bool $required = false): string {
	ob_start();
	$priorityStates = [
			'WA' => 'Washington',
			'ID' => 'Idaho',
			'BC' => 'British Columbia',
	];
	$states = [
			'AL' => 'Alabama',
			'AK' => 'Alaska',
			'AS' => 'American Samoa',
			'AZ' => 'Arizona',
			'AR' => 'Arkansas',
			'CA' => 'California',
			'CO' => 'Colorado',
			'CT' => 'Connecticut',
			'DE' => 'Delaware',
			'DC' => 'District of Columbia',
			'FL' => 'Florida',
			'GA' => 'Georgia',
			'GU' => 'Guam',
			'HI' => 'Hawaii',
			'ID' => 'Idaho',
			'IL' => 'Illinois',
			'IN' => 'Indiana',
			'IA' => 'Iowa',
			'KS' => 'Kansas',
			'KY' => 'Kentucky',
			'LA' => 'Louisiana',
			'ME' => 'Maine',
			'MD' => 'Maryland',
			'MA' => 'Massachusetts',
			'MI' => 'Michigan',
			'MN' => 'Minnesota',
			'MS' => 'Mississippi',
			'MO' => 'Missouri',
			'MT' => 'Montana',
			'NE' => 'Nebraska',
			'NV' => 'Nevada',
			'NH' => 'New Hampshire',
			'NJ' => 'New Jersey',
			'NM' => 'New Mexico',
			'NY' => 'New York',
			'NC' => 'North Carolina',
			'ND' => 'North Dakota',
			'MP' => 'Northern Mariana Islands',
			'OH' => 'Ohio',
			'OK' => 'Oklahoma',
			'OR' => 'Oregon',
			'PA' => 'Pennsylvania',
			'PR' => 'Puerto Rico',
			'RI' => 'Rhode Island',
			'SC' => 'South Carolina',
			'SD' => 'South Dakota',
			'TN' => 'Tennessee',
			'TX' => 'Texas',
			'VI' => 'U.S. Virgin Islands',
			'UT' => 'Utah',
			'VT' => 'Virginia',
			'WA' => 'Washington',
			'WV' => 'West Virginia',
			'WI' => 'Wisconsin',
			'WY' => 'Wyoming',
	];
	$provinces = [
			'AB' => 'Alberta',
			'BC' => 'British Columbia',
			'MB' => 'Manitoba',
			'NB' => 'New Brunswick',
			'NL' => 'Newfoundland and Labrador',
			'NT' => 'Northwest Territories',
			'NS' => 'Nova Scotia',
			'NU' => 'Nunavut',
			'ON' => 'Ontario',
			'PE' => 'Prince Edward Island',
			'QC' => 'Quebec',
			'SK' => 'Saskatchewan',
			'YT' => 'Yukon',
	];
	?>
	<label for="<?=$prefix?>Address" class="span-2<?=$required ? " required" : ""?>"><?=$label?></label>
	<input type="text" name="<?=$prefix?>Address" id="<?=$prefix?>Address"<?=$required ? " required" : ""?>
			title="Address" autocomplete="street-address">
	<div class="label hidden"></div>
	<div class="city-st-zip">
		<label for="<?=$prefix?>City">City</label>
		<input type="text" name="<?=$prefix?>City" id="<?=$prefix?>City"<?=$required ? " required" : ""?> title="City"
				autocomplete="address-level2">
		<label for="<?=$prefix?>State">State</label>
		<select id="<?=$prefix?>State" name="<?=$prefix?>State"<?=$required ? " required" : ""?> title="State"
				autocomplete="address-level1">
			<option selected aria-label="Please select"></option>
			<?=options($priorityStates)?>
			<option class="spacer" disabled></option>
			<?=options($states)?>
			<option class="spacer" disabled></option>
			<?=options($provinces)?>
			<option class="spacer" disabled></option>
			<option value="NA" aria-label="Not applicable">N/A</option>
		</select>
		<label for="<?=$prefix?>Zip">Zip/Postal code</label>
		<input type="text" name="<?=$prefix?>Zip" id="<?=$prefix?>Zip"
				pattern=" *(([0-9]{5}(-?[0-9]{4})?)|([A-Za-z][0-9][A-Za-z] ?[0-9][A-Za-z][0-9]))? *"
				title="US ZIP code (00000 or 00000-0000) or Canadian postal code (A0A 0A0)" autocomplete="postal-code">
	</div>
	<?php
	return ob_get_clean();
}

?>
<!DOCTYPE html>
<html lang="en-US">
<head>
	<title>Adoption Application - <?=_G_longname()?></title>
	<meta charset="UTF-8">
	<?php if ($_GET): ?>
		<meta name="robots" content="noindex">
	<?php endif; ?>
	<meta name="viewport" content="width=device-width">
	<?php
	style();
	emailLinks();
	style("application", true, "20220219");
	style("minheader", true, "20220219");
	?>
	<script src="events.js"></script>
	<script src="/formenter.js"></script>
	<link rel="canonical" href="https://<?=_G_public_domain()?>/application">
	<link href="https://unpkg.com/filepond/dist/filepond.css" rel="stylesheet" data-remove="true">
	<link
			href="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css"
			rel="stylesheet" data-remove="true">
</head>
<body>
<?php
ob_start();
pageHeader();
echo str_replace("<header>", "<header data-remove='true'>", ob_get_clean());
?>
<article>
	<section id="thanks" data-if-config="main" data-rhs="false" class="noprint">
		<?php
		application_response();
		?>
	</section>
	<header data-if-config="minhead" id="minimal_header">
		<a href="/">
			<h1><?=_G_shortname()?></h1>
			<div>
				<address><p><?=mb_strcut(str_replace("\n", "<p>", _G_address()), 0, -5)?></address>
				<span class="tel"><?=_G_phone()?></span>
			</div>
		</a>
	</header>
	<form method="POST" enctype="multipart/form-data" id="application">
		<h2 data-if-config="main" data-rhs="false" data-hidden="false">Adoption Application</h2>
		<p data-if-config="weblink"><a data-href-config="path">View application on the web</a>
			<?php // TODO [#143]: Display a modal for application faq ?>
		<p data-remove="true" class="noprint">Please read the <a href="faq.php" target="_blank">application FAQ</a> before
			filling this out.
			<input type="hidden" name="form_id" value="application">
		<section id="basic_information">
			<h3>Basic information</h3>
			<div class="info_grid">
				<div class="spacer"></div>
				<section class="applicant">
					<h4>Applicant</h4>
					<label for="AName" class="required">Name</label>
					<input type="text" name="AName" id="AName" required autocomplete="name">
					<?php
					echo addressInput("Mailing address", "A", true);
					echo addressInput('Physical address <span class="explanatory">(if different)</span>', "AP");
					?>
					<label for="APhone" class="required">Phone</label>
					<input type="tel" name="APhone" id="APhone" required autocomplete="tel">
					<label for="AEmail" class="required">Email</label>
					<input type="email" name="AEmail" id="AEmail" required autocomplete="email">
					<label for="ADOB" class="required">Date of birth</label>
					<input type="date" name="ADOB" id="ADOB" required autocomplete="bday">
					<label for="AEmployer" class="required">Employer</label>
					<input type="text" name="AEmployer" id="AEmployer" required autocomplete="organization">
				</section>
				<section class="coapplicant">
					<h4>Co-applicant
						<span class="explanatory">(if different)</span>
					</h4>
					<label for="CName">Name</label>
					<input type="text" name="CName" id="CName" autocomplete="name">
					<?php
					echo addressInput("Mailing address", "C");
					echo addressInput('Physical address <span class="explanatory">(if different)</span>', "CP");
					?>
					<label for="CPhone">Phone</label>
					<input type="tel" name="CPhone" id="CPhone" autocomplete="tel">
					<label for="CEmail">Email</label>
					<input type="email" name="CEmail" id="CEmail" autocomplete="email">
					<label for="CDOB">Date of birth</label>
					<input type="date" name="CDOB" id="CDOB" autocomplete="bday">
					<label for="CEmployer">Employer</label>
					<input type="text" name="CEmployer" id="CEmployer" autocomplete="organization">
				</section>
			</div>
		</section>
		<section id="household_information">
			<h3>Household information</h3>
			<section id="other_people">
				<h4>Other people in the household</h4>
				<div class="people_table">
					<h5 class="name">Name</h5>
					<h5 class="dob">Date of birth</h5>
					<div class="name" data-foreach="PeopleName"></div>
					<div class="dob" data-foreach="PeopleDOB"></div>
					<ul data-remove="true">
						<!-- Form fields will be injected by the event handler. -->
						<li class="add">
							<button class="add"><span>➕</span> Add another</button>
						</li>
					</ul>
				</div>
			</section>
			<div class="animals">
				<section id="animals_current">
					<h4>Animals currently residing with you</h4>
					<h5 class="name">Pet's Name</h5>
					<h5 class="species">Species</h5>
					<h5 class="breed">Breed</h5>
					<h5 class="age">Age</h5>
					<h5 class="gender">Gender</h5>
					<!-- @formatter:off -->
					<h5 class="spayed">Spayed/<wbr>Neutered?</h5>
					<!-- @formatter:on -->
					<div class="name" data-foreach="CurrentName"></div>
					<div class="species" data-foreach="CurrentSpecies"></div>
					<div class="breed" data-foreach="CurrentBreed"></div>
					<div class="age" data-foreach="CurrentAge"></div>
					<div class="gender" data-foreach="CurrentGender"></div>
					<div class="spayed" data-foreach="CurrentFixed"></div>
					<ul data-remove="true">
						<!-- Form fields will be injected by the event handler. -->
						<li class="add">
							<button class="add"><span>➕</span> Add another</button>
						</li>
					</ul>
				</section>
				<section id="animals_past">
					<h4>Animals no longer residing with you</h4>
					<h5 class="name">Pet's Name</h5>
					<h5 class="species">Species</h5>
					<h5 class="breed">Breed</h5>
					<h5 class="reason">Reason for loss</h5>
					<div class="name" data-foreach="PastName"></div>
					<div class="species" data-foreach="PastSpecies"></div>
					<div class="breed" data-foreach="PastBreed"></div>
					<div class="reason" data-foreach="PastAge"></div>
					<ul data-remove="true">
						<!-- Form fields will be injected by the event handler. -->
						<li class="add">
							<button class="add"><span>➕</span> Add another</button>
						</li>
					</ul>
				</section>
			</div>
		</section>
		<section id="adoption_information">
			<h3>Adoption information</h3>
			<section id="types_of_animals">
				<p>Which types of animals are you interested in?</p>
				<label>
					<input type="checkbox" name="adult_dog"> Adult dog
				</label>
				<label>
					<input type="checkbox" name="puppy"> Puppy
				</label>
				<label>
					<input type="checkbox" name="adult_cat"> Adult cat
				</label>
				<label>
					<input type="checkbox" name="kitten"> Kitten
				</label>
				<div class="other">
					<label>
						<input type="checkbox" name="other"> <span class="other_label">Other</span>
					</label>
					<label for="other_specify">Please specify</label>
					<input type="text" name="other_specify" id="other_specify" title="Please specify" disabled>
				</div>
				<div class="preference">
					<p>Preference: </p>
					<label>
						<input type="radio" name="preference" value="male"> Male
					</label>
					<label>
						<input type="radio" name="preference" value="female"> Female
					</label>
					<label>
						<input type="radio" name="preference" value="either"> Either<span data-if="preference"
								data-rhs="either"> male or female</span>
					</label>
				</div>
			</section>
			<section id="particular">
				<p>Are you applying for a particular animal listed on our website?</p>
				<div>
					<label>
						<input type="radio" name="particular" value="y" required<?=$_GET['pet'] ??
						false ? ' checked' : ''?>> Yes
					</label>
					<label>
						<input type="radio" name="particular" value="n" required> No
					</label>
				</div>
				<label data-if="particular" data-rhs="y" data-hidden="false">
					Please specify:
					<input type="text" name="particular_specify"
							value="<?=(($_GET['pet'] ?? false) && $pet = $db->getPetById($_GET['pet'])) ?
									$pet->id . ' ' . $pet->name . ($pet->friend ? " &amp; {$pet->friend->id} {$pet->friend->name}" : "") :
									''?>">
				</label>
			</section>
			<section id="qualities">
				<label class="textarea">
					<span>What qualities and characteristics do you desire in your new pet?</span>
					<textarea name="qualities" required></textarea>
				</label>
			</section>
		</section>
		<section id="about_home">
			<h3>About your home</h3>
			<section id="residence">
				<label class="textarea">
					<span>Please describe your residence:</span>
					<textarea name="residence" required></textarea>
				</label>
				<input type="hidden" id="will_live_tracker" name="will_live_tracker" value="0">
				<div class="residence_grid">
					<p>The residence is:</p>
					<input type="radio" id="owned" name="residence_is" value="owned" required>
					<label for="owned">Owned</label>
					<input type="radio" id="rented" name="residence_is" value="rented" required>
					<label for="rented">Rented</label>
					<p>The pet will live:</p>
					<input type="radio" id="live_inside" name="will_live" value="inside" required>
					<label for="live_inside">Inside</label>
					<input type="radio" id="live_outside" name="will_live" value="outside" required>
					<label for="live_outside">Outside</label>
					<input type="radio" id="live_both" name="will_live" value="both" required>
					<label for="live_both">Both<span data-if="will_live" data-rhs="both"> inside and outside</span></label>
				</div>
				<p class="rented" data-if-config="showrentp" data-hidden="0">
					Please attach below, email to <a data-email></a>, or fax to <?=_G_fax()?> a copy of the pet clause of your
					lease or other written permission, along with contact information for your landlord or managing agent.
				</p>
				<p data-if-config="outside_warn" data-value-config="outside_message"></p>
			</section>
			<section id="outside" data-if="will_live" data-operator="ne" data-rhs="inside"
					data-hidden="false">
				<div>
					<p>Is the yard fenced? </p>
					<label>
						<input type="radio" name="Fence" value="Y"> Yes
					</label>
					<label>
						<input type="radio" name="Fence" value="N"> No
					</label>
					<label class="fence_description textarea">
						<span class="fence-unspecified" data-if="Fence" data-rhs="" data-hidden="0">Please describe the height and type of fencing, or if no fence, how you plan to exercise and confine the pet:</span>
						<span class="fence-yes" data-if="Fence" data-rhs="Y" data-hidden="0">Please describe the height and type of fencing:</span>
						<span class="fence-no" data-if="Fence" data-rhs="N" data-hidden="0">Please describe how you plan to exercise and confine the pet:</span>
						<textarea name="fence_description"></textarea>
					</label>
				</div>
				<div class="fieldset">
					<fieldset>
						<legend>When outside, the pet will be:</legend>
						<input type="radio" id="chained_tied" name="when_outside" value="chained_tied">
						<label for="chained_tied">Chained/tied</label>
						<input type="radio" id="fenced_in_yard" name="when_outside" value="fenced_in_yard">
						<label for="fenced_in_yard">Fenced in yard</label>
						<input type="radio" id="leashed" name="when_outside" value="leashed">
						<label for="leashed">Leashed</label>
						<input type="radio" id="free_to_roam" name="when_outside" value="free_to_roam">
						<label for="free_to_roam">Free to roam</label>
					</fieldset>
				</div>
			</section>
			<label>
				<span>Where will the pet sleep at night?</span>
				<input type="text" name="sleep" class="sleep">
			</label>
			<label class="textarea">
				<span>Approximately how many hours per week will the pet be left without human companionship?
				Will the pet be indoors or outdoors when alone?
					What other pets will be with this pet?</span>
				<textarea name="companionship"></textarea>
			</label>
		</section>
		<section id="references">
			<h3>References</h3>
			<div>
				<section id="veterinarian">
					<div>
						<h4>Veterinarian</h4>
						<p class="explanatory">If you do not have a current vet, a past vet is fine; if this is your first pet,
							please
							tell us what vet you plan to use.</p>
					</div>
					<div>
						<input type="text" id="vet_name" name="vet_name" required>
						<label for="vet_name" class="explanatory required">Name</label>
						<textarea id="vet_address" name="vet_address" required></textarea>
						<label for="vet_address" class="explanatory required">Address</label>
						<input type="tel" id="vet_phone" name="vet_phone" required>
						<label for="vet_phone" class="explanatory required">Phone</label>
					</div>
				</section>
				<div class="spacer"></div>
				<section id="personal_reference">
					<div>
						<h4>Personal Reference</h4>
						<p class="explanatory">If there is anyone who is particularly familiar with your pets and your pet care who
							would like to act as a reference for you, please list them here.</p>
					</div>
					<div>
						<div class="spacer"></div>
						<input type="text" id="ref_name" name="ref_name">
						<label for="ref_name" class="explanatory">Name</label>
						<input type="text" id="ref_contact" name="ref_contact">
						<label for="ref_contact" class="explanatory">Phone&nbsp;or email</label>
						<div class="spacer"></div>
					</div>
				</section>
			</div>
		</section>
		<section id="attachments">
			<h3 class="noprint" data-remove="true">Attachments</h3>
			<h3 data-if-config="main">Attachments</h3>
			<div data-remove="true" class="noprint">
				<p>Add any attachments below, or email them to <a data-email></a> after submitting your application.</p>
				<p>If you live outside the Republic/Curlew area, please attach or email photos of your home.</p>
			</div>
			<input type="file" id="images" name="images[]" accept="image/*,application/pdf" capture="environment"
					multiple class="noprint">
			<span class="limits explanatory" data-remove="true">
            (max. 64 MB each, 512 MB total)
			</span>
			<ul class="thumbnails" data-if-config="thumbnails">
				<li data-foreach="images" data-as="image">
					<a data-href="image" data-file-transformer="url">
						<span data-value="image" data-file-transformer="thumbnail"></span>
					</a>
				</li>
			</ul>
			<ul data-if-config="thumbnails" data-rhs="false">
				<li data-foreach="images" data-as="image" data-if-config="main">
					<a data-href="image" data-file-transformer="url">
						<span data-value="image" data-if-config="thumbnails" data-rhs="false"></span>
					</a>
				</li>
				<li data-foreach="images" data-if-config="main" data-rhs="false"></li>
			</ul>
		</section>
		<section id="comments" data-if="comments" data-hidden="false">
			<h3><label for="comments_box">Comments</label></h3>
			<textarea name="comments" id="comments_box"></textarea>
		</section>
		<section id="submit" data-remove="true">
			<button type="submit">Submit Application</button>
		</section>
	</form>
	<script src="https://unpkg.com/filepond/dist/filepond.js"></script>
	<script
			src="https://unpkg.com/filepond-plugin-image-exif-orientation/dist/filepond-plugin-image-exif-orientation.js"></script>
	<script src="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.js"></script>
	<script src="https://unpkg.com/filepond-plugin-image-transform/dist/filepond-plugin-image-transform.js"></script>
	<script
			src="https://unpkg.com/filepond-plugin-file-validate-type/dist/filepond-plugin-file-validate-type.js"></script>
	<script
			src="https://unpkg.com/filepond-plugin-file-validate-size/dist/filepond-plugin-file-validate-size.js"></script>
	<script>
		// TODO [#66]: Asynchronous attachment upload
		// TODO [#276]: Use image editor plugin
		FilePond.registerPlugin(FilePondPluginImageExifOrientation);
		FilePond.registerPlugin(FilePondPluginImagePreview);
		FilePond.registerPlugin(FilePondPluginImageTransform);
		FilePond.registerPlugin(FilePondPluginFileValidateType);
		FilePond.registerPlugin(FilePondPluginFileValidateSize);
		const pond = FilePond.create(document.querySelector('input#images'), {
			maxFileSize: '64MB',
			maxTotalFileSize: '512MB',
			imagePreviewMinHeight: 0,
			imagePreviewMaxHeight: 128,
			storeAsFile: true,
		});
	</script>
</article>

<?php
ob_start();
footer();
echo str_replace("<footer>", "<footer data-remove='true'>", ob_get_clean());
?>
</body>
</html>
