<?php
require_once "../../src/common.php";
require_once "../../src/form.php";
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
    <h1>Error <?=$e->getCode()?></h1>
    <p>Something went wrong submitting the form: <?=$e->getMessage()?>
    <p><a href="/">Back to homepage</a>
        <!--
<?php
        var_dump($e)
        ?>
    -->
    </html>
    <?php
};

$formConfig->emails = function(array $formData): array {
    $shelterEmail = new EmailAddress(_G_default_email_user() . '@' . _G_public_domain(), _G_shortname());
    $applicantEmail = new EmailAddress($formData['applicant_email'], $formData['applicant_name']);

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

    return [$primaryEmail, $secondaryEmail];
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
    <label for="applicant_email">Email</label>
    <input id="applicant_email" type="email" name="applicant_email" data-transformer="email-link" required>
    <br><input type="text" name="list_input[]" value="value 1">
    <br><input type="text" name="list_input[]" value="value 2">
    <br><input type="text" name="list_input[]" value="value 3" data-remove="false">
    <br><input type="text" name="list_input[]" value="value 4" data-ignore="true">
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
                <option>Diplodocus</option>
                <option>Saltasaurus</option>
                <option>Apatosaurus</option>
            </optgroup>
        </select>
        <label for="selector">Label for the selector</label>
    </fieldset>
    </p>
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
</fieldset>
</body>
</html>