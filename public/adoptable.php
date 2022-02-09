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

/**
 * @param string $basePath A path such as Cats/C1411Autumn
 * @return string A path such as C1411Autumn or /Cats/C1411Autumn
 */
function relativePath(string $basePath): string {
	global $path;
	$p = trim($path, "/") . "/";
	if (startsWith($p, $basePath)) {
		return substr($basePath, strlen($p));
	}
	return "/$basePath";
}

?>
<!DOCTYPE html>
<html lang="en-US">
<title><?=htmlspecialchars(ucfirst($species->plural()))?> for adoption at <?=_G_longname()?></title>
<meta charset="utf-8">
<?php
style();
style("adoptable", false, "20220208");
style("adoptable.generated", false, "20220208"); // I hate this
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
			$href .= htmlspecialchars($pet->id);
			$href .= htmlspecialchars(str_replace(" ", "", $pet->name));
			$href .= '"';
		}

		echo '<tr class="st_';
		echo $pet->status->key;
		if (!$listed) {
			echo ' soon';
		}
		echo '">';

		echo '<th class="name"><a';
		echo $href;
		echo ' id="' . htmlspecialchars($pet->id) . '">';
		echo htmlspecialchars($pet->name);
		echo '</a></th>';

		echo '<td class="sex">';
		echo ucfirst(@($pet->sex->name) ?? "");
		echo " " . $pet->breed;
		echo '</td>';

		echo '<td class="age">';
		echo "<time datetime=\"{$pet->dob}\">";
		echo $pet->age();
		echo '</time></td>';

		echo '<td class="fee"><div></div><span>';
		echo $pet->fee;
		echo '</span></td>';

		echo '<td class="img"><a';
		echo $href;
		echo '>';
		echo $pet->photo?->imgTag(htmlspecialchars($pet->name), false, false, 300);
		echo '</a></td>';

		echo '<td class="inquiry"><a data-email></a></td>';
	}
	?>
	</tbody>
</table>
<section>
	<p><strong>Adoption Fees</strong> include Vaccinations and Spay/Neuter!
		<?php
		foreach (_G_statuses() as $status) {
			/* @var $status Status */
			$description = (isset($status->description) && $status->description !== null) ? $status->description :
					"";
			if (isset($status->displayStatus) && $status->displayStatus && strlen(trim($description)) > 0) {
				echo "<p><strong>{$status->name}:</strong><br>";
				echo nl2br(htmlspecialchars($description), false);
			}
		}
		?>
</section>
<hr>
<section>
	<p>We always need <b>LOVE LOVE LOVE</b> for the fuzzballs! Want to brush a cat or walk a dog? We need you! You
		can volunteer as little as 3 hours a month. Call <a href="tel:+15097752308">775-2308</a> or email <a
				data-email></a>
</section>
<?php footer(); ?>
</html>
