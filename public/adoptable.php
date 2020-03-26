<?php
require_once("../src/common.php");
require_once("$t/footer.php");
require_once("$src/db.php");
require_once("$src/css.php");
if (isset($_GET["species"])) {
	$species = _G_species()[intval($_GET["species"])];
}
if (!isset($species)) {
	log_err("Unknown species");
	require_once("$src/errors/510.php");
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
	<title><?=htmlspecialchars(ucfirst($species->__get("plural")))?> for adoption at <?=_G_longname()?></title>
<?php
style();
style("adoptable");
?>
	<style type="text/css">
		<?php
		$displayedStatusSelectors = array();
		$hoverStatusSelectors = array();
		foreach (_G_statuses() as $status) {
			/* @var $status Status */
			if (isset($status->displayStatus) && $status->displayStatus) {
				$sel = "tr.st_{$status->key}";
				$displayedStatusSelectors[] = $sel;
				if (isset($status->description) && strlen(trim($status->description)) > 0) {
					$hoverStatusSelectors[] = $sel;

					// The content to display when hovering over the status
					echo $sel . '>td.fee::before{content:"';
					echo cssspecialchars($status->name . ":\\A" . $status->description);
					echo '";} "';
				}
			}
		}

		if (count($displayedStatusSelectors)) {
			// Display pending animals with a grey background
			echo buildSelector($displayedStatusSelectors, " *");
			echo "{background-color:#ddd;} ";
		}

		if (count($hoverStatusSelectors)) {
			// Display the ? to hover over to see the status
			echo buildSelector($hoverStatusSelectors, ">td.fee>*::after") . <<<CSS
			{
				content: "?";
				margin-left: 0.5ex;
				color: #00f;
				font-size: 9pt;
				border: 1pt solid #00f;
				padding: 0.1em;
				width: 1em;
				height: 1em;
				line-height: 1em;
				border-radius: 1em;
				vertical-align: 0.1em;
				display: inline-block;
				cursor: default;
			} 
			CSS;
			// TODO: minify CSS on-the-fly?

			// Make the ? a different color when hovering
			echo buildSelector($hoverStatusSelectors, ">td.fee>*:hover::after");
			echo "{background-color:#00f;color:#fff;} ";

			// Hide the ? when printing
			echo "@media print {";
			echo buildSelector($hoverStatusSelectors, ">td.fee>*::after");
			echo "{display: none;} } ";

			// Make the popup able to overflow outside the box when hovering
			echo buildSelector($hoverStatusSelectors, ">td.fee>*::after");
			echo "{overflow:visible;position:relative;} ";

			// Style the popup
			echo buildSelector($hoverStatusSelectors, ">td.fee::before") . <<<CSS
			{
				width: 100%;
				border-radius: 0.5em;
				border: 1px solid black;
				position: absolute;
				left: 50%;
				top: 1.3em;;
				margin-top: 0;
				transform: translate(-50%, 10px);
				background-color: #fff;
				color: #000;
				padding: 1em;
				opacity: 0;
				box-shadow: -2pt 2pt 5pt #000;
				text-align: justify;
				text-justify: inter-character;
				z-index: -1;
			}
			CSS;

			// popup transition
			echo buildSelector($hoverStatusSelectors, ">td.fee:hover::before");
			echo "{opacity:0.9;transition:all 0.18s ease-out 0.18s;z-index:2;} ";
			// TODO: mobile friendly popup
		}
		?>
	</style>
	<h2>Adoptable <?=$species->__get("plural")?></h2>
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
		$db   = new Database();
		$pets = $db->getAdoptablePetsBySpecies($species);
		foreach ($pets as $pet) {
			/* @var $pet Pet */
			$listed = ($pet->description !== null && strlen(trim($pet->description->fetch())) > 0) || ($pet->photos !== null && count($pet->photos) > 0);

			$href = "";
			if ($listed) {
				$href .= ' href="';
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
			echo ucfirst($pet->sex->name);
			if (strlen(trim($pet->breed)) > 2) {
				echo " " . $pet->breed;
			}
			echo '</td>';

			echo '<td class="age">';
			echo "<time datetime=\"{$pet->dob}\">";
			echo $pet->age();
			echo '</time></td>';

			echo '<td class="fee"><div></div><span>';
			if ($pet->status->displayStatus) {
				echo $pet->status->name;
			} else {
				echo $pet->fee;
			}
			echo '</span></td>';

			echo '<td class="img"><a';
			echo $href;
			echo '><img src="';
			echo relativePath($pet->photo->path);
			echo '" alt="';
			echo htmlspecialchars($pet->name);
			echo '"></a></td>';

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
				$description = (isset($status->description) && $status->description !== null) ? $status->description : "";
				if (isset($status->displayStatus) && $status->displayStatus && strlen(trim($description)) > 0) {
					echo "<p><strong>{$status->name}:</strong><br>";
					echo nl2br(htmlspecialchars($description));
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