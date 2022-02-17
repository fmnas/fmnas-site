<?php
require_once "../src/common.php";
require_once "$t/header.php";
require_once "$t/footer.php";
require_once "$src/db.php";
require_once "$src/css.php";
if (isset($_GET["species"])) {
	$species = _G_species()[intval($_GET["species"])];
}
if (!isset($species)) {
	log_err("Unknown species");
	require_once "$src/errors/510.php";
	exit();
}
/* @var $species Species */
/* @var $path string */
?>
<!DOCTYPE html>
<html lang="en-US">
<title><?=htmlspecialchars(ucfirst($species->plural()))?> for adoption at <?=_G_longname()?></title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="canonical" href="https://<?=_G_public_domain()?>/<?=$species->plural()?>">
<?php
style();
style("adoptable");
emailLinks();
?>
<script src="/email.js.php"></script>
<?php
pageHeader();
?>
<h2>Adoptable <?=$species->plural()?></h2>
<table class="listings">
	<thead>
	<tr>
		<th>Name</th>
		<th>Sex</th>
		<th>Age</th>
		<th>Adoption fee</th>
		<th>Image</th>
		<th>Email inquiry</th>
	</tr>
	</thead>
	<tbody>
	<?php
	$db ??= new Database();
	$pets = $db->getAdoptablePetsBySpecies($species);
	foreach ($pets as $pet) {
		/* @var $pet Pet */
		$listed = $pet->listed();

		$href = "";
		if ($listed) {
			$href .= " href=\"/$path/";
			$href .= htmlspecialchars($pet->path);
			$href .= '"';
		}

		echo '<tr class="st_';
		echo $pet->status->key;
		if (!$listed) {
			echo ' soon';
		}
		if ($pet->status->displayStatus) {
			echo ' displayStatus';
			if (trim($pet->status->description ?? '')) {
				echo ' explain';
			}
		}
		if ($pet->bonded) {
			echo ' pair';
		}
		echo '">';

		echo '<th class="name"><a';
		echo $href;
		if ($pet->bonded) {
			echo '><ul><li id="' . htmlspecialchars($pet->id) . '">';
			echo htmlspecialchars($pet->name);
			echo '<li id="' . htmlspecialchars($pet->friend?->id) . '">';
			echo htmlspecialchars($pet->friend?->name);
			echo '</li></ul>';
		} else {
			echo ' id="' . htmlspecialchars($pet->id) . '">';
			echo htmlspecialchars($pet->name);
		}
		echo '</a></th>';

		echo '<td class="sex">';
		if (($lsex = ucfirst(@($pet->sex->name) ?? "") . " " . $pet->breed) ===
				($rsex = ucfirst(@($pet->friend->sex->name) ?? "") . " " . $pet->friend?->breed) ||
				!$pet->bonded) {
			echo $lsex;
		} else {
			echo "<ul><li>$lsex<li>$rsex</ul>";
		}
		echo '</td>';

		echo '<td class="age">';
		if (!$pet->friend?->dob || $pet->age() === $pet->friend->age()) {
			echo "<time datetime=\"{$pet->dob}\">";
			echo $pet->species->age($pet->dob);
			echo '</time>';
		} else {
			echo '<ul><li>';
			echo "<time datetime=\"{$pet->dob}\">";
			echo $pet->species->age($pet->dob);
			echo '</time>';
			echo '<li>';
			echo "<time datetime=\"{$pet->friend->dob}\">";
			echo $pet->species->age($pet->friend->dob);
			echo '</time>';
			echo '</li></ul>';
		}
		echo '</td>';

		echo '<td class="fee"><span class="fee">';
		echo $pet->status->displayStatus ? $pet->status->name :
				($listed ? ($pet->fee . $pet->bonded ? '' : ' BONDED PAIR') : 'Coming Soon');
		echo '</span>';
		if ($pet->status->displayStatus && trim($pet->status->description ?? '')) {
			echo '<aside class="explanation">';
			echo nl2br(htmlspecialchars($pet->status->description));
			echo '</aside>';
		}
		echo '</td>';

		echo '<td class="img"><a';
		echo $href;
		echo '>';
		if (!$pet->friend?->photo?->key || $pet->friend->photo->key === $pet->photo->key) {
			echo $pet->photo?->imgTag($pet->name, false, false, 300);
		} else {
			echo '<ul><li>';
			echo $pet->photo?->imgTag($pet->name, false, false, 300);
			echo '<li>';
			echo $pet->friend->photo?->imgTag($pet->friend->name, false, false, 300);
			echo '</li></ul>';
		}
		echo '</a></td>';

		echo '<td class="inquiry"><a data-email></a></td>';
	}
	?>
	</tbody>
</table>
<table class="listings last-row">
	<tbody></tbody>
</table>
<script src="/adoptable.js"></script>
<section class="explanations">
	<aside class="info"><strong>Adoption Fees</strong> include Vaccinations and Spay/Neuter!</aside>
	<?php
	foreach (_G_statuses() as $status) {
		/* @var $status Status */
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$description = (isset($status->description) && $status->description !== null) ? $status->description :
				"";
		if (isset($status->displayStatus) && $status->displayStatus && strlen(trim($description)) > 0) {
			echo "<aside class=\"info st_" . $status->key . "\"><strong>{$status->name}:</strong><br>";
			echo nl2br(htmlspecialchars($description), false);
			echo "</aside>";
		}
	}
	?>
</section>
<?php footer(); ?>
</html>
