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
    $applicantEmail = new EmailAddress($formData['AEmail'], $formData['AName']);

    $hash = sha1(print_r($formData, true));
    $path = "https://" . _G_public_domain() . "/application/received/$hash.html";

    $dump = new FormEmailConfig(
        null,
        [],
        '',
        ['main' => true, 'path' => $path, 'thumbnails' => true]
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
        ['main' => true, 'path' => $path, 'thumbnails' => true]
    );
    $save->saveFile = "$cwd/received/$hash.html";

    $primaryEmail = new FormEmailConfig(
        $applicantEmail,
        [$shelterEmail],
        'Adoption Application from ' . $formData['applicant_name'],
        ['main' => true, 'path' => $path, 'weblink' => true,
            'outside_warn' => $formData['will_live'] === 'inside' && $formData['will_live_tracker'],
            'outside_message' => 'Warning: This applicant checked then unchecked "pet will live outside."',
        ]
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
        $secondaryEmail->cc = [new EmailAddress($formData['CEmail'], $formData['CName'])];
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
    <h2 data-if-config="main" data-rhs="false" data-hidden="false">Adoption Application</h2>
    <p data-if-config="weblink"><a data-href-config="path">View application on the web</a>
    <p data-remove="true">Please read the <a href="faq.htm">application FAQ</a> before filling this out.
    <form method="POST" enctype="multipart/form-data" id="application">
        <input type="hidden" name="form_id" value="application">
        <section id="basic_information">
            <h3>Basic information</h3>
        </section>
        <section id="household_information">
            <h3>Household information</h3>
            <section id="other_people">
                <h4>Other people in the household</h4>
            </section>
            <section id="animals_current">
                <h4>Animals you currently own</h4>
            </section>
            <section id="animals_past">
                <h4>Animals you have owned in the past</h4>
            </section>
        </section>
        <section id="adoption_information">
            <h3>Adoption information</h3>
        </section>
        <section id="about_home">
            <h3>About your home</h3>
            <section id="residence">
                <input type="hidden" id="will_live_tracker" name="will_live_tracker" value="0">
                <input type="radio" id="live_inside" name="will_live" value="inside">
                <label for="live_inside">Inside</label>
                <input type="radio" id="live_outside" name="will_live" value="outside">
                <label for="live_outside">Outside</label>
                <input type="radio" id="live_both" name="will_live" value="both">
                <label for="live_both">Both</label>
            </section>
            <section id="outside">
                <p data-if-config="outside_warn" data-value-config="outside_message"></p>
                outside
            </section>
        </section>
        <section id="references">
            <h3>References</h3>
        </section>
        <section id="attachments">
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
            <span class="limits" data-remove="true">
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
        <section id="submit">
            <button type="submit">Submit Application</button>
        </section>
    </form>
</article>
</body>
</html>