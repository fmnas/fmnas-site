<?php
require_once "../src/common.php";
require_once "$src/pet.php";
require_once "$src/db.php";
require_once "$src/assets.php";
require_once "$t/header.php";
require_once "$t/footer.php";
/* @var $path string */
/* @var $species Species */
$db ??= new Database();
if (!($pet = $db->getPetByPath($path))) {
    return; // this is not a valid listing
}
?>
    <!DOCTYPE html>
    <title>
        <?=htmlspecialchars($pet->name)?>, <?=$pet->species()?>
        <?=$pet->status->listed ? "for adoption at" : ($pet->status->name == _G_statuses()[1]->name ? "adopted from" : "-")?>
        <?=_G_longname()?>
    </title>
    <meta charset="utf-8">
    <meta name="robots" content="<?=$pet->status->listed ? "index" : "noindex"?>">
<?php
style();
style("listing");
emailLinks();
pageHeader();
?>
    <a class="return" href="/<?=$pet->species->plural()?>" title="<?=ucfirst($pet->species->plural())?>">
        Return to the <?=$pet->species->pluralWithYoung()?> page
    </a>
    <article>
    <h2><?=$pet?></h2>
    <p class="subtitle"><?php
echo $pet->age();
echo '&nbsp;&middot;&nbsp;';
if (!$pet->status->listed || $pet->status->displayStatus) {
    echo $pet->status->name;
} else {
    echo $pet->status->name;
    echo '&nbsp;&middot;&nbsp;';
    echo $pet->fee;
}
?>
    <aside class="images">
<?php foreach ($pet->photos as $photo) {
    if ($photo === null) {
        break;
    }
    /* @var $photo Asset */
    echo $photo->imgTag(null, true, false, 640);
}
echo '</aside>';
if ($pet->description !== null) {
    echo '<section id="description">';
    echo $pet->description->parse([
        "pet"  => $pet->toArray(),
        "name" => $pet->name,
        "fee"  => $pet->fee,
    ]);
    echo '</section>';
}
echo '</article>';
footer();
exit(0); // Exit from handler.php if this is indeed a listing