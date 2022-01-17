<?php
/**
 * Include this file at the top of a form page, then set values of $formConfig.
 *
 * The <form> element will be replaced with an <article> element.
 * All <input> and <select> elements will be replaced with <span> elements, except:
 *   input[type="button"], input[type="submit"], input[type="reset"], input[type="password"], input[type="hidden"],
 *   and input[type="image"] will be removed unless they have an explicit falsy data-remove attribute.
 * All <textarea> elements will be replaced with <pre> elements.
 * All <fieldset> elements will be replaced with <section> elements containing <h3> headers.
 * All <button> elements will be removed unless they have an explicit falsy data-remove attribute, in which case they
 * will be replaced with <span> elements.
 * All <script> elements will be removed unless they have an explicit falsy data-remove attribute.
 * All <label> elements will be replaced with span elements. The inner text will itself be in a span element with the
 * class label-value.
 * All generated elements will have a data-type attribute with the original tag ("form", "input", etc.)
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
 *   <p data-if-config="operator-email"><span data-value="name"></span> submitted the following form.
 *
 * To transform the inner HTML of an element after all other processing is complete, add a data-transformer attribute:
 *   <input type="email" name="email" value="example@example.com" data-transformer="email-link">
 * This must correspond to an entry in the $formConfig->transformers associative array, which is a closure that
 * transforms one HTML string into another HTML string.
 * The default $formConfig includes the following transformers:
 *   - email-link: wraps an email address in a mailto link (useful with input[type="email"])
 *   - tel-link: wraps a phone number in a tel link (useful with input[type="tel"])
 *   - link: wraps a URL in a link (useful with input[type="url"])
 *
 * data-transformer-if and data-transformer-if-config do what you expect.
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
 *
 * @todo Add unit tests for the form processor and maybe split it into a separate repo.
 */

use JetBrains\PhpStorm\Pure;
use Masterminds\HTML5;
use PHPMailer\PHPMailer\PHPMailer;

$pwd = getcwd();

$phpmailer_path ??= '../src/PHPMailer';
$html5_php_path ??= '../src/html5-php';
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
require_once("$html5_php_path/src/HTML5/Parser/Scanner.php");
require_once("$html5_php_path/src/HTML5/Parser/Tokenizer.php");
require_once("$html5_php_path/src/HTML5/Parser/TreeBuildingRules.php");
require_once("$html5_php_path/src/HTML5/Parser/UTF8Utils.php");
require_once("$html5_php_path/src/HTML5/Serializer/HTML5Entities.php");
require_once("$html5_php_path/src/HTML5/Serializer/OutputRules.php");
require_once("$html5_php_path/src/HTML5/Serializer/Traverser.php");
require_once("$html5_php_path/src/HTML5/Parser/DOMTreeBuilder.php");
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

    public string $smtpHost;
    public string $smtpSecurity;
    public int $smtpPort;
    public bool $smtpAuth;
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
    public function __construct(public EmailAddress $from, public iterable $to, public string $subject,
        public ?array $values = []) {
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
        // @todo Move injected styles to head.
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
 * @throws \PHPMailer\PHPMailer\Exception
 * @throws DOMException
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
 * @throws \PHPMailer\PHPMailer\Exception
 */
function sendEmail(FormEmailConfig $emailConfig, string $emailBody) {
    global $formConfig;

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
    $mailer->IsHTML(true);
    $mailer->Subject = $emailConfig->subject;
    $mailer->Body = $emailBody;
    var_dump($mailer); // @todo Remove mailer dump
    $mailer->Send();
}

/**
 * Copy all attributes from one DOMElement to another.
 * @param DOMElement $from The element that has the attributes.
 * @param DOMElement $to The element that wants the attributes.
 * @param string ...$exclude A list of attributes not to copy.
 * @return void
 */
function copyAttributes(DOMElement &$from, DOMElement &$to, string ...$exclude): void {
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
function moveChildren(DOMElement &$from, DOMElement &$to): void {
    while ($from->firstChild) {
        $to->appendChild($from->firstChild);
    }
}

/**
 * Check truthiness of a string. Defined the same as in PHP, except "false" is falsy.
 * @param string $str A string
 * @return bool Whether the string is truthy.
 */
function checkString(string $str): bool {
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
 */
function collectElements(DOMElement|DOMDocument &$root, string $tag = "*", ?Closure $filter = null): array {
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
    return $elements;
}

/**
 * Generates a closure that returns true if the given element has the given attribute.
 * @param string $attribute An attribute to check
 * @return Closure to be passed to collectElements
 */
function has(string $attribute): Closure {
    return function(DOMElement $element) use ($attribute): bool {
        return $element->hasAttribute($attribute);
    };
}

/**
 * Generates a closure that returns true if the given attribute on the given element is truthy.
 * @param string $attribute An attribute to check
 * @return Closure to be passed to collectElements
 */
function truthy(string $attribute): Closure {
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
function attr(string $attribute, string $value): Closure {
    return function(DOMElement $element) use ($attribute, $value): bool {
        return $element->getAttribute($attribute) === $value;
    };
}

/**
 * Render the HTML to send in an email.
 * @param array $data The submitted form data.
 * @param array|null $values Additional values to use when rendering data-value.
 * @param string $html The server-rendered HTML of the empty form.
 * @return string Rendered HTML containing the form values.
 * @throws Exception
 * @throws DOMException
 */
function renderForm(array $data, string $html, ?array $values = []): string {
    global $formConfig, $pwd;

    $values ??= [];
    $html5 = new HTML5(["xmlNamespaces" => false]);
    $dom = $html5->loadHTML($html);
    $forms = $dom->getElementsByTagName('form');
    if ($forms->length === 0) {
        throw new Exception('No form elements were found in the rendered HTML.');
    }
    if ($forms->length > 1) {
        throw new Exception('Multiple form elements were found in the rendered HTML.');
    }
    $originalForm = $forms[0];
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
        $span->setAttribute("data-type", "span");
        $inputName = $input->getAttribute("name");
        copyAttributes($input, $span, "value", "required");
        switch ($input->getAttribute("type")) {
            // @todo Support input[type="color"]
            // @todo Support input[type="file"]
        case 'button':
        case 'submit':
        case 'reset':
        case 'password':
        case 'image':
            /** @noinspection PhpMissingBreakStatementInspection */
        case 'hidden':
            if (!$input->hasAttribute("data-remove")) {
                $span->setAttribute("data-remove", "1");
                break;
            }
            // Fall through to standard input handler in case it isn't to be removed.
        case 'text':
        case 'checkbox': // @todo Consider handling checkboxes differently.
        case 'radio': // @todo Consider handling radio buttons differently.
        case 'date':
        case 'datetime-local':
        case 'email':
        case 'month':
        case 'number':
        case 'range':
        case 'tel':
        case 'time':
        case 'url':
        case 'week':
        default:
            if (endsWith($inputName, "[]") && !$input->hasAttribute("data-remove")) {
                // Remove repeated inputs by default.
                $span->setAttribute("data-remove", "1");
            }
            $span->nodeValue = htmlspecialchars($data[$inputName] ?? $input->getAttribute('value'));
        }
        var_dump($dom->saveHTML($span));
        $input->parentNode?->replaceChild($span, $input);
    }

    // @todo Replace select elements with span elements.

    // @todo Replace textarea elements with pre elements.

    // @todo Replace fieldset elements with section elements with h3 headers.

    // @todo Replace button elements with span elements (hidden by default).

    // @todo Deal with label elements.

    // Merge linked style sheets into the output HTML.
    foreach (collectElements($dom, "link", attr("rel", "stylesheet")) as $link) {
        /** @var $link DOMElement */
        $href = $link->getAttribute("href");
        if (is_file("$pwd/$href")) {
            ob_start();
            include "$pwd/$href";
            $styles = ob_get_clean();
        } else {
            if (!ini_get('allow_url_fopen')) {
                // Can't get the file :(
                continue;
            }
            if (startsWith($href, "/")) {
                /** @noinspection HttpUrlsUsage */
                $protocol = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https://" : "http://";
                $path = startsWith($href, "//") ? $protocol . $href :
                    $protocol . $_SERVER["HTTP_HOST"] . $href;
                $styles = file_get_contents($path);
            } else {
                $styles = file_get_contents($href);
            }
        }
        if ($styles === false) {
            // Failed to get styles; delegate responsibility to the email client.
            continue;
        }
        $style = $dom->createElement("style");
        $style->setAttribute("type", "text/css");
        copyAttributes($link, $style, "rel", "href");
        $style->nodeValue = $styles;
        $link->parentNode?->replaceChild($style, $link);
    }

    // Mark all script elements with data-remove.
    foreach (collectElements($dom, "script") as $script) {
        /** @var $script DOMElement */
        if (!$script->hasAttribute("data-remove")) {
            $script->setAttribute("data-remove", "1");
            break;
        }
    }

    // Remove all elements marked with data-remove.
    foreach (collectElements($dom, "*", truthy("data-remove")) as $element) {
        /** @var $element DOMElement */
        $element->parentNode?->removeChild($element);
    }

    // Apply transformers.
    foreach (collectElements($dom, "*", has("data-transformer")) as $element) {
        /** @var $element DOMElement */
        $transformer = $element->getAttribute("data-transformer");
        if (isset($formConfig->transformers[$transformer])) {
            $innerHTML = "";
            while ($element->hasChildNodes()) {
                $innerHTML .= $dom->saveHTML($element->firstChild);
                $element->removeChild($element->firstChild);
            }
            $transformed = $formConfig->transformers[$transformer]($innerHTML);
            $fragment = $element->ownerDocument->createDocumentFragment();
            $fragment->appendXML($transformed);
            $element->appendChild($fragment);
        } else {
            echo "Warning: transformer $transformer not found; leaving value untransformed.";
        }
    }

    return $dom->saveHTML();
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

ob_start();
register_shutdown_function('collectForm');
