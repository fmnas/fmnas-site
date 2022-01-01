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


use JetBrains\PhpStorm\Pure;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
$phpmailer_path ??= '../lib/PHPMailer';
require "$phpmailer_path/src/Exception.php";
require "$phpmailer_path/src/PHPMailer.php";
require "$phpmailer_path/src/SMTP.php";

/**
 * The FormConfig class contains data that shall not be output to the browser
 * used to process the form. After including the form processor, the global
 * $formConfig contains an instance of this class populated with defaults.
 * Update these values to control the behavior of the form processor.
 */
class FormConfig {
    /**
     * Callback for when the form is submitted. This should be used to output
     * a thank you message, etc.
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

    public string $smtpHost;
    public string $smtpSecurity;
    public int $smtpPort;
    public string $smtpUser;
    public string $smtpPassword;
}

/**
 * The configuration for a single email sent after form processing.
 */
class FormEmailConfig {
    /**
     * FormEmailConfig constructor.
     * @param EmailAddress $from The email for the From header.
     * @param iterable<EmailAddress> $to The emails for the To header.
     * @param string $subject The value for the Subject header.
     * @param array|null $values An optional array of arbitrary data accessible
     * when rendering the email. If an emelent in the form has a data-value
     * attribute, its value will be used as a key when accessing this array.
     * For example, <input type="hidden" data-value="foo"> will be rendered as
     * <span>{{$values['foo']}}</span>
     */
    public function __construct(public EmailAddress $from, public iterable $to, public string $subject, public ?array $values = []) {
        $this->replyTo = [];
        $this->cc = [];
        $this->bcc = [];
    }

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

    #[Pure] public function __construct(Exception $e, ?array $data) {
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
function collectForm() {
    global $formConfig;

    // Get the server-rendered form HTML from the page output.
    $html = ob_get_clean();

    // Get the submitted POST or GET data.
    $receivedData = isset($_POST['submit']) ? $_POST : $_GET;

    // @todo Devise a way to allow multiple forms on a page.
    if (isset($receivedData['submit'])) {
        try {
            // Process the form and send emails.
            processForm($receivedData, $html);

            // Call the confirm callback.
            ($formConfig->confirm)();
        } catch (Exception $e) {
            ($formConfig->handler)(new FormException($e, $receivedData));
        }
    } else {
        // Display the form.
        // @todo Inject styles into HTML for hidden elements.
        echo $html;
        ?>
        <style>
            body {
                background-color: blue;
            }
        </style>
        <?php
    }
}

register_shutdown_function('collectForm');

/**
 * Send emails containing the submitted form data.
 * @param array $data Raw form data ($_GET or $_POST).
 * @throws Exception
 */
function processForm(array $data, string $html) {
    global $formConfig;

    foreach (($formConfig->emails)($data) as $emailConfig) {
        /** @var $emailConfig FormEmailConfig */
        $renderedForm = renderForm($data, $html, $emailConfig->values);

        sendEmail($emailConfig, $renderedForm);
    }
}

/**
 * Send an email.
 * @param FormEmailConfig $emailConfig
 * @param string $emailBody The HTML email body.
 * @throws Exception
 */
function sendEmail(FormEmailConfig $emailConfig, string $emailBody) {
    global $formConfig;
    $mailer = new PHPMailer();
    $mailer->IsSMTP();
    $mailer->SMTPAuth = true;
    $mailer->SMTPSecure = $formConfig->smtpSecurity;
    $mailer->Host = $formConfig->smtpHost;
    $mailer->SMTPAuth = true;
    $mailer->Username = $formConfig->smtpUser;
    $mailer->Password = $formConfig->smtpPassword;
    $mailer->From = $emailConfig->from->address;
    if ($emailConfig->from->name) {
        $mailer->FromName = $emailConfig->from->name;
    }
    foreach($emailConfig->to as $to) {
        /** @var $to EmailAddress */
        $mailer->AddAddress($to->address, $to->name ?: '');
    }
    foreach($emailConfig->replyTo as $replyTo) {
        /** @var $replyTo EmailAddress */
        $mailer->AddReplyTo($replyTo->address, $replyTo->name ?: '');
    }
    foreach($emailConfig->cc as $cc) {
        /** @var $cc EmailAddress */
        $mailer->AddCc($cc->address, $cc->name ?: '');
    }
    foreach($emailConfig->bcc as $bcc) {
        /** @var $bcc EmailAddress */
        $mailer->AddBcc($bcc->address, $bcc->name ?: '');
    }
    $mailer->IsHTML(true);
    $mailer->Subject = $emailConfig->subject;
    $mailer->Body = $emailBody;
    $mailer->Send();
}

/**
 * Render the HTML to send in an email.
 * @param array $data The submitted form data.
 * @param array|null $values Additional values to use when rendering data-value.
 * @param string $html The server-rendered HTML of the empty form.
 * @return string Rendered HTML containing the form values.
 */
function renderForm(array $data, string $html, ?array $values = []): string {
    $values ??= [];
    // @todo Render form.
    return print_r([$data, $values, $html], true);
}

$formConfig = new FormConfig();
// Default values for $formConfig.
$formConfig->confirm      = function(array $formData): void {
    ?>
    <!DOCTYPE html>
    <title>Thank you!</title>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex,nofollow">
    <h1>Thank You</h1>
    <p>We have received your form submission.
    <p><a href="/">Back to homepage</a>
    <p>The received form data was:
    <pre>
    <?php
    var_dump($formData);
};
$formConfig->handler      = function(FormException $e): void {
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <title>Error</title>
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
$formConfig->emails       = function(array $formData): array {
    $email = new EmailAddress('webmaster@' . $_SERVER['HTTP_HOST']);
    $config = new FormEmailConfig($email, [$email], 'Form Data');
    return [$config];
};
$formConfig->smtpHost   = 'localhost';
$formConfig->smtpSecurity = 'tls';
$formConfig->smtpPort     = 25;
$formConfig->smtpUser     = 'root';
$formConfig->smtpPassword = '';
