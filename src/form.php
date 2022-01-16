<?php

/**
 * Include this file at the top of a form page, then set values of $formConfig.
 *
 * The <form> element will be replaced with an <article> element.
 * All <input> and <select> elements will be replaced with <span> elements, except:
 *   input[type="button"], input[type="submit"], input[type="reset"], input[type="password"] will be removed unless
 *   they have an explicit falsy data-remove attribute, in which case they will be replaced with <span> elements.
 * All <textarea> elements will be replaced with <pre> elements.
 * All <fieldset> elements will be replaced with <section> elements containing <h3> headers.
 * All <button> elements will be hidden unless they have an explicit falsy data-remove attribute, in which case they
 * will be replaced with <div> elements.
 *
 * Elements with a truthy data-ignore attribute will not be modified.
 * Elements with a truthy data-remove attribute will be removed from the rendered email.
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
 * To add output only used in the rendered email, use the data-hidden attribute. Elements with this attribute will be
 * hidden on the form page by injected CSS:
 *   <p data-hidden>Thank you for submitting the form! A copy of the received data is below.
 *
 * To use values from the form to generate output:
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
 * value would otherwise be provided:
 *   <p data-if-config="user-email">Thanks for submitting the form!
 *   <p data-if-config="operator-email"><span data-value="name"></span> submitted the following form:
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
 */

use JetBrains\PhpStorm\Pure;
use Masterminds\HTML5;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

$phpmailer_path ??= '../lib/PHPMailer';
$html5_php_path ??= '../lib/html5-php';
require "$phpmailer_path/src/Exception.php";
require "$phpmailer_path/src/PHPMailer.php";
require "$phpmailer_path/src/SMTP.php";
require "$html5_php_path/src/HTML5.php";

// @todo Figure out how to get PSR-4 autoloading without composer.
// Relevant: https://akrabat.com/using-composer-with-shared-hosting/
require_once("$html5_php_path/src/HTML5/Elements.php");
require_once("$html5_php_path/src/HTML5/Entities.php");
require_once("$html5_php_path/src/HTML5/Exception.php");
require_once("$html5_php_path/src/HTML5/InstructionProcessor.php");
require_once("$html5_php_path/src/HTML5/Parser/EventHandler.php");
require_once("$html5_php_path/src/HTML5/Serializer/RulesInterface.php");
require_once("$html5_php_path/src/HTML5/Parser/InputStream.php");
require_once("$html5_php_path/src/HTML5/Parser/StringInputStream.php");
require_once("$html5_php_path/src/HTML5/Parser/Scanner.php");
require_once("$html5_php_path/src/HTML5/Parser/Tokenizer.php");
require_once("$html5_php_path/src/HTML5/Parser/TreeBuildingRules.php");
require_once("$html5_php_path/src/HTML5/Parser/UTF8Utils.php");
require_once("$html5_php_path/src/HTML5/Serializer/HTML5Entities.php");
require_once("$html5_php_path/src/HTML5/Serializer/OutputRules.php");
require_once("$html5_php_path/src/HTML5/Serializer/Traverser.php");
require_once("$html5_php_path/src/HTML5/Parser/DOMTreeBuilder.php");
require_once("$html5_php_path/src/HTML5/Parser/FileInputStream.php");
require_once("$html5_php_path/src/HTML5/Parser/ParseError.php");
require_once("$html5_php_path/src/HTML5/Parser/CharacterReference.php");

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
     * FormEmailConfig constructor.
     * @param EmailAddress $from The email for the From header.
     * @param iterable<EmailAddress> $to The emails for the To header.
     * @param string $subject The value for the Subject header.
     * @param array|null $values An optional array of arbitrary data accessible
     * when rendering the email. If an element in the form has a data-value
     * attribute, its value will be used as a key when accessing this array.
     * For example, <input type="hidden" data-value="foo"> will be rendered as
     * <span>{{$values['foo']}}</span>
     */
    public function __construct(public EmailAddress $from, public iterable $to, public string $subject, public ?array $values = []) {
        $this->replyTo = [];
        $this->cc = [];
        $this->bcc = [];
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
function collectForm() {
    global $formConfig;

    // Get the server-rendered form HTML from the page output.
    $html = ob_get_clean();

    // Get the submitted POST or GET data.
    $receivedData = $_POST ?: $_GET;

    // @todo Devise a way to allow multiple forms on a page.
    if ($receivedData || $_FILES) {
        try {
            processForm($receivedData, $html);
            ($formConfig->confirm)($receivedData);
        } catch (Exception $e) {
            ($formConfig->handler)(new FormException($e, $receivedData));
        }
    } else {
        // Display the form.
        echo $html;
        ?>
        <style>
            /* Injected styles */
        </style>
        <?php
    }
}

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
    $mailer = new PHPMailer(true);
    $mailer->IsSMTP();
    $mailer->Host = $formConfig->smtpHost;
    $mailer->Port = $formConfig->smtpPort;
    $mailer->SMTPAuth = true;
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
 * @throws Exception
 */
function renderForm(array $data, string $html, ?array $values = []): string {
    $values ??= [];
    $html5 = new HTML5();
    $dom = $html5->loadHTML($html);
    $forms = $dom->getElementsByTagName('form');
    if ($forms->length === 0) {
        throw new Exception('No form elements were found in the rendered HTML.');
    }
    if ($forms->length > 1) {
        throw new Exception('Multiple form elements were found in the rendered HTML.');
    }
    $form = $forms[0];
    return $dom->saveHTML($form);
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
    <p>The received form data was:
    <pre>
    <?php
    var_dump($formData);
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
    <p>The submitted form data was:
    <pre>
    <?php
    var_dump($e->formData);
};
$formConfig->emails = function(array $formData): array {
    $email = new EmailAddress('webmaster@' . $_SERVER['HTTP_HOST']);
    $config = new FormEmailConfig($email, [$email], 'Form Data');
    return [$config];
};
$formConfig->smtpHost = 'localhost';
$formConfig->smtpSecurity = '';
$formConfig->smtpPort = 25;
$formConfig->smtpUser = 'root';
$formConfig->smtpPassword = '';

ob_start();
register_shutdown_function('collectForm');
