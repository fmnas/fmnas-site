<?php
require_once "../../src/common.php";
require_once "../../src/form.php";
ini_set('upload_max_filesize', '10M');
ini_set('max_file_uploads', '20');
ini_set('file_uploads', true);
ini_set('post_max_size', '200M');
setlocale(LC_ALL, 'en_US.UTF-8');
set_time_limit(300);
$formConfig->confirm = function(array $formData): void {
    ?>
    <!DOCTYPE html>
    <html lang="en-US">
    <title>Adoption Application - <?=_G_longname()?></title>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex,nofollow">
    <?php style(); ?>
    <h1>Adoption Application</h1>
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
    <h1>Error <?=$e->getCode()?></h1>
    <p>Something went wrong submitting the form: <?=$e->getMessage()?>
    <p>Please contact Sean at <a data-email="sean"></a> with the following information:
    <pre><?php
        var_dump($e)
        ?></pre>
    <p><a href="/">Back to homepage</a>
    </html>
    <?php
    // Attempt to email the PHP context to Sean so he can fix it.
    @sendEmail(
        new FormEmailConfig(
            new EmailAddress("admin@forgetmenotshelter.org"),
            new EmailAddress("sean@forgetmenotshelter.org"),
            "Application Error Context"),
        new RenderedEmail(
            '<pre>' . print_r(get_defined_vars(), true) . '</pre>',
            []));
};

$cwd = getcwd();
$formConfig->emails = function(array $formData) use ($cwd): array {
    $shelterEmail = new EmailAddress(_G_default_email_user() . '@' . _G_public_domain(), _G_shortname());
    $applicantEmail = new EmailAddress($formData['applicant_email'], $formData['applicant_name']);

    $dump = new FormEmailConfig(
        null,
        [],
        '',
        ['main' => true]
    );

    $dump->fileDir = function(array $file) use ($cwd): string {
        return $file["type"] === "image/jpeg" ? "$cwd/received" : "";
    };
    $dump->saveFile = "$cwd/received/last.html";
    $dump->hashFilenames = function(array $file): HashOptions {
        return startsWith($file["type"], "image/") ? HashOptions::NO : HashOptions::YES;
    };
    $dump->globalConversion = true;

    $primaryEmail = new FormEmailConfig(
        $applicantEmail,
        [$shelterEmail],
        'Adoption Application from ' . $formData['applicant_name'],
        ['main' => true]
    );

    $secondaryEmail = new FormEmailConfig(
        $shelterEmail,
        [$applicantEmail],
        'Your ' . _G_longname() . ' Adoption Application',
        ['main' => false]
    );

    if (isset($formData['coapplicant_email'])) {
        $secondaryEmail->cc = [new EmailAddress($formData['coapplicant_email'], $formData['applicant_name'])];
    }

    return [$dump, $primaryEmail, $secondaryEmail];
};
$formConfig->fileTransformers["url"] = function(array $metadata): string {
    return "url transformer not yet implemented";
};
$formConfig->smtpHost = Config::$smtp_host;
$formConfig->smtpSecurity = Config::$smtp_security;
$formConfig->smtpPort = Config::$smtp_port;
$formConfig->smtpUser = Config::$smtp_username;
$formConfig->smtpPassword = Config::$smtp_password;
$formConfig->smtpAuth = Config::$smtp_auth;
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
    <title>Adoption Application - <?=_G_longname()?></title>
    <meta charset="UTF-8">
    <meta name="robots" content="nofollow">
    <script src="/email.js.php"></script>
    <?php
    style();
    style("application", true);
    ?>
</head>
<body>
Application
<form method="POST" enctype="multipart/form-data" id="application">
    <label>Name
        <input type="text" name="applicant_name" required>
    </label>
    <h1 data-if-config="main">Main email</h1>
    <h1 data-if-config="main" data-operator="eq" data-rhs="false">Secondary email</h1>
    <h1 data-if="applicant_email" data-operator="eq" data-rhs-value="applicant_email">Always true.</h1>
    <h1 data-if="applicant_email" data-operator="ne" data-rhs-value="applicant_email">Never true.</h1>
    <label for="applicant_email">Email</label>
    <h1 data-value="applicant_email" data-transformer="email-link" data-transformer-if-config="main"></h1>
    <h1 data-value="applicant_email" data-transformer="email-link" data-transformer-if="applicant_email"
        data-transformer-operator="ne" data-transformer-rhs="tortoise@panray.seangillen.net"></h1>
    <input id="applicant_email" type="email" name="applicant_email" data-transformer="email-link" required>
    <br><input type="text" name="list_input[]" value="value 1">
    <br><input type="text" name="list_input[]" value="value 2">
    <br><input type="text" name="list_input[]" value="value 3" data-remove="false">
    <br><input type="text" name="list_input[]" value="value 4" data-ignore="true">
    <ul style="color: blue">
        <li data-foreach="list_input" data-as="list_input_value">
            The value is <span data-value="list_input_value">???</span>
        </li>
    </ul>
    <ul style="color: green">
        <li data-foreach="list_input">
    </ul>
    <p>
    <fieldset form="application">
        <legend>Some additional fields</legend>
        <textarea name="information">
            This is the textarea.
        </textarea>
    </fieldset>
    <fieldset>
        <select name="selection" id="selector">
            <optgroup label="Theropods">
                <option>Tyrannosaurus</option>
                <option>Velociraptor</option>
                <option>Deinonychus</option>
            </optgroup>
            <optgroup label="Sauropods">
                <option value="diplo">Diplodocus</option>
                <option>Saltasaurus</option>
                <option>Apatosaurus</option>
                <option value="BLANK"></option>
            </optgroup>
        </select>
        <label for="selector">Label for the selector</label>
        <h1 data-if="selection" data-rhs="diplo">WUB WUB</h1>
        <h1 data-if="selection" data-rhs="Saltasaurus">salty boi</h1>
    </fieldset>
    <label for="images">Upload some images
        <input type="file" id="images" name="images[]" accept="image/*" capture="environment" multiple>
    </label>
    <label for="file">Upload another file</label>
    <input type="file" id="file" name="file">
    <pre data-foreach="images" data-file-transformer="dump"></pre>
    <ul>
        <li data-foreach="images" data-as="image">
            <a data-href="image" data-file-transformer="url">
                <span data-value="image"></span>
            </a>
        </li>
    </ul>
    <button type="submit">Submit Application</button>
</form>
<fieldset form="application">
    <legend>Choose your favorite monster</legend>

    <input type="radio" id="kraken" name="monster" value="krak">
    <label for="kraken">Kraken</label><br/>

    <input type="radio" id="sasquatch" name="monster" value="sasq">
    <label for="sasquatch">Sasquatch</label><br/>

    <legend>An extraneous legend</legend>

    <input type="radio" id="mothman" name="monster" value="moth">
    <label for="mothman">Mothman</label>

    <p>Choose your monster's features:</p>

    <div>
        <input type="checkbox" id="scales" name="scales"
            checked>
        <label for="scales">Scales</label>
    </div>

    <div>
        <input type="checkbox" id="horns" name="horns">
        <label for="horns">Horns</label>
    </div>
</fieldset>
</body>
</html>