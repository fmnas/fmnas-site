<?php
require_once "../../src/common.php";
require_once "../../src/form.php";
require_once "$t/header.php";
require_once "$t/application_response.php";
ini_set('upload_max_filesize', '10M');
ini_set('max_file_uploads', '20');
ini_set('file_uploads', true);
ini_set('post_max_size', '200M');
ini_set('memory_limit', '2048M');
setlocale(LC_ALL, 'en_US.UTF-8');
set_time_limit(300);
$formConfig->confirm = function(array $formData): void {
	?>
	<!DOCTYPE html>
	<html lang="en-US">
	<title>Adoption Application - <?=_G_longname()?></title>
	<meta charset="UTF-8">
	<meta name="robots" content="noindex,nofollow">
	<?php
	style();
	pageHeader();
	?>
	<h2>Adoption Application</h2>
	<p>Thank you! We have received your application and you will hear back from us soon.
	<p><a href="/">Return to the shelter homepage</a>
	</html>
	<?php
};
$formConfig->handler = function(FormException $e): void {
	http_response_code(500);
	?>
	<!DOCTYPE html>
	<html lang="en-US">
	<title>Adoption Application - <?=_G_longname()?></title>
	<meta charset="UTF-8">
	<meta name="robots" content="noindex,nofollow">
	<script src="/email.js.php"></script>
	<?php
	style();
	pageHeader();
	?>
	<h2>Error <?=$e->getCode() ?: 500?></h2>
	<p>Something went wrong submitting the form: <?=$e->getMessage()?>
	<p><img src="//http.cat/500" alt="">
	<p>Please contact Sean at <a data-email="sean"></a> with the following information:
	<pre><?php
		var_dump($e);
		?>
    </pre>
	<p><a href="/">Return to the shelter homepage</a>
	</html>
	<?php
	// Attempt to email the PHP context to Sean so he can fix it.
	@sendEmail(
			new FormEmailConfig(
					new EmailAddress("admin@forgetmenotshelter.org"),
					[new EmailAddress("sean@forgetmenotshelter.org")],
					"Application Error Context"),
			new RenderedEmail(
					'<pre>' . print_r(get_defined_vars(), true) . '</pre>',
					[]));
};

$cwd = getcwd();
$formConfig->emails = function(array $formData) use ($cwd): array {
	$shelterEmail = new EmailAddress(_G_default_email_user() . '@' . _G_public_domain(), _G_shortname());
	$applicantEmail = new EmailAddress(trim($formData['AEmail']), trim($formData['AName']));

	$hash = sha1(print_r($formData, true));
	$path = "https://" . _G_public_domain() . "/application/received/$hash.html";

	$outside_warn = $formData['will_live'] === 'inside' && $formData['will_live_tracker'];
	$outside_message = 'Warning: This applicant checked then unchecked "pet will live outside."';

	$dump = new FormEmailConfig(
			null,
			[],
			'',
			['main' => true, 'path' => $path, 'weblink' => true,
					'outside_warn' => $outside_warn, 'outside_message' => $outside_message,]
	);

	$dump->fileDir = function(array $file) use ($cwd): string {
		return $file["type"] === "image/jpeg" ? "$cwd/received" : "";
	};
	$dump->hashFilenames = HashOptions::SAVED_ONLY;
	$dump->globalConversion = true;

	$save = new FormEmailConfig(
			null,
			[],
			'',
			['main' => true, 'path' => $path, 'thumbnails' => true, 'minhead' => true,
					'outside_warn' => $outside_warn, 'outside_message' => $outside_message,]
	);
	$save->saveFile = "$cwd/received/$hash.html";

	$primarySubject = 'Adoption Application from ' . trim($formData['AName']);
	if (trim($formData['CName'] ?? '')) {
		$primarySubject .= ' and ' . $formData['CName'];
	}

	$primaryEmail = new FormEmailConfig(
			$applicantEmail,
			[$shelterEmail],
			$primarySubject,
			['main' => true, 'path' => $path, 'weblink' => true,
					'outside_warn' => $outside_warn, 'outside_message' => $outside_message,]
	);
	$primaryEmail->attachFiles = function(array $metadata): bool {
		$total_size = 0;
		foreach ($_FILES as $file_input) {
			if (is_array($file_input["size"])) {
				foreach ($file_input["size"] as $size) {
					$total_size += $size;
				}
			} else {
				$total_size += $file_input["size"];
			}
		}
		return $total_size < 20 * 1048576;
	};

	$secondaryEmail = new FormEmailConfig(
			$shelterEmail,
			[$applicantEmail],
			'Your ' . _G_longname() . ' Adoption Application',
			['main' => false]
	);
	$secondaryEmail->attachFiles = false;
	if ($formData['CEmail']) {
		$secondaryEmail->cc = [new EmailAddress(trim($formData['CEmail']), trim($formData['CName']))];
	}

	if (file_exists($save->saveFile)) {
		echo '<!-- Application not sent - detected duplicate at ' . $save->saveFile . ' -->';
		return [];
	}

	return [$dump, $save, $primaryEmail, $secondaryEmail];
};
$formConfig->fileTransformers["url"] = function(array $metadata): string {
	return "https://" . _G_public_domain() . "/application/received/" . $metadata["hash"];
};
$formConfig->transformers["mailto"] = function(string $email): string {
	return "mailto:$email";
};
$formConfig->smtpHost = Config::$smtp_host;
$formConfig->smtpSecurity = Config::$smtp_security;
$formConfig->smtpPort = Config::$smtp_port;
$formConfig->smtpUser = Config::$smtp_username;
$formConfig->smtpPassword = Config::$smtp_password;
$formConfig->smtpAuth = Config::$smtp_auth;

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
			title="Address">
	<div class="label hidden"></div>
	<div class="city-st-zip">
		<label for="<?=$prefix?>City">City</label>
		<input type="text" name="<?=$prefix?>City" id="<?=$prefix?>City"<?=$required ? " required" : ""?> title="City">
		<label for="<?=$prefix?>State">State</label>
		<select id="<?=$prefix?>State" name="<?=$prefix?>State"<?=$required ? " required" : ""?> title="State">
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
				title="US ZIP code (00000 or 00000-0000) or Canadian postal code (A0A 0A0)">
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
	<meta name="robots" content="nofollow">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="/email.js.php"></script>
	<?php
	style();
	style("application", true);
	?>
	<script src="events.js"></script>
	<script src="/formenter.js"></script>
</head>
<body>
<?php
ob_start();
pageHeader();
echo str_replace("<header>", "<header data-remove='1'>", ob_get_clean());
?>
<article>
	<section id="thanks" data-if-config="main" data-rhs="false">
		<?php
		application_reponse();
		?>
	</section>
	<header data-if-config="minhead" data-hidden="false" class="printonly" id="minimal_header">
		<a href="/">
			<h1><?=_G_shortname()?></h1>
			<address><p><?=mb_strcut(str_replace("\n", "<p>", _G_address()), 0, -5)?></address>
			<span class="tel"><?=_G_phone()?></span>
		</a>
	</header>
	<form method="POST" enctype="multipart/form-data" id="application">
		<h2 data-if-config="main" data-rhs="false" data-hidden="false">Adoption Application</h2>
		<p data-if-config="weblink"><a data-href-config="path">View application on the web</a>
			<?php // @todo Display a modal for application faq ?>
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
					<input type="text" name="AName" id="AName" required>
					<?php
					echo addressInput("Mailing address", "A", true);
					echo addressInput('Physical address <span class="explanatory">(if different)</span>', "AP");
					?>
					<label for="APhone" class="required">Phone</label>
					<input type="tel" name="APhone" id="APhone" required>
					<label for="AEmail" class="required">Email</label>
					<input type="email" name="AEmail" id="AEmail" required>
					<label for="ADOB" class="required">Date of birth</label>
					<input type="date" name="ADOB" id="ADOB" required>
					<label for="AEmployer" class="required">Employer</label>
					<input type="text" name="AEmployer" id="AEmployer" required>
				</section>
				<section class="coapplicant">
					<h4>Co-applicant
						<span class="explanatory">(if different)</span>
					</h4>
					<label for="CName">Name</label>
					<input type="text" name="CName" id="CName">
					<?php
					echo addressInput("Mailing address", "C");
					echo addressInput('Physical address <span class="explanatory">(if different)</span>', "CP");
					?>
					<label for="CPhone">Phone</label>
					<input type="tel" name="CPhone" id="CPhone">
					<label for="CEmail">Email</label>
					<input type="email" name="CEmail" id="CEmail">
					<label for="CDOB">Date of birth</label>
					<input type="date" name="CDOB" id="CDOB">
					<label for="CEmployer">Employer</label>
					<input type="text" name="CEmployer" id="CEmployer">
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
					<h5 class="fixed">Spayed/<wbr>Neutered?</h5>
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
		</section>
		<section id="about_home">
			<h3>About your home</h3>
			<section id="residence">
				<input type="hidden" id="will_live_tracker" name="will_live_tracker" value="0">
				<input type="radio" id="live_inside" name="will_live" value="inside" required>
				<label for="live_inside">Inside</label>
				<input type="radio" id="live_outside" name="will_live" value="outside" required>
				<label for="live_outside">Outside</label>
				<input type="radio" id="live_both" name="will_live" value="both" required>
				<label for="live_both">Both</label>
			</section>
			<section id="outside" data-if="will_live_tracker" data-operator="ne" data-rhs="inside" data-hidden="false">
				<p data-if-config="outside_warn" data-value-config="outside_message"></p>
				outside
			</section>
		</section>
		<section id="references">
			<h3>References</h3>
		</section>
		<section id="attachments" class="noprint">
			<h3>Attachments</h3>
			<div data-remove="true">
				<p>Add any attachments below, or email them to <a data-email></a> after submitting your application.</p>
				<p>If you live outside the Republic/Curlew area, please add photos of your home.</p>
			</div>
			<?php
			// @todo Better image upload interface
			// @todo Enforce image size limit on client side
			?>
			<input type="file" id="images" name="images[]" accept="image/*,application/pdf" capture="environment"
					multiple>
			<span class="limits explanatory" data-remove="true">
            (max. 10 MB each, 200 MB total)
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
		<section id="comments">
			<h3>Comments</h3>
		</section>
		<section id="submit" data-remove="true">
			<button type="submit">Submit Application</button>
		</section>
	</form>
</article>
</body>
</html>
