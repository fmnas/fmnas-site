<?php
require_once "../../src/common.php";
require_once "../../src/form.php";
$formConfig->confirm = function(array $formData): void {
?>
<!DOCTYPE html>
<title>Adoption Application - <?=_G_longname()?></title>
<meta charset="UTF-8">
<meta name="robots" content="noindex,nofollow">
<?php style(); ?>
<h1>Adoption Application</h1>
<p>Thank you! We have received your application and you will hear back from us soon.
<p><a href="/">Return to the shelter homepage</a>
    <?php
    };
    $formConfig->handler = function(FormException $e): void {
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <title>>Adoption Application - <?=_G_longname()?></title>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex,nofollow">
<h1>Error <?=$e->getCode()?></h1>
<p>Something went wrong submitting the form: <?=$e->getMessage()?>
<p><a href="/">Back to homepage</a>
<p>The submitted form data was:
<pre>
    <?php
    var_dump($e->formData);
    };
    $formConfig->emails = function(array $formData): array {
        $shelterEmail     = new EmailAddress('admin@forgetmenotshelter.org');
        $applicantEmail   = new EmailAddress($formData['applicant_email'], $formData['applicant_name']);

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
    $formConfig->smtpServer = 'localhost';
    $formConfig->smtpPort = 25;
    $formConfig->smtpUser = 'root';
    $formConfig->smtpPassword = '';
    ?>
<!DOCTYPE html>
<title>Adoption Application - <?=_G_longname()?></title>
<meta charset="UTF-8">
    <meta name="robots" content="nofollow">
<?php style(); ?>
Application
<form method="POST" enctype="multipart/form-data">
    <label>Name
        <input type="text" name="applicant_name" required>
    </label>
    <label>Email
        <input type="text" name="applicant_email" required>
    </label>
    <button formaction="submit">Submit Application</button>
</form>