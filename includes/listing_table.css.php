<?php
	require_once($_SERVER['DOCUMENT_ROOT'].'/includes/paths.php');
	require_once("$BASE/includes/db.php");
	require_once("$BASE/includes/css.php");
	header('Content-Type: text/css');
?>

/*<style type="text/css"> for code highlighting */

/*Listings table - individual listings*/
table.listings tbody tr {
	display: inline-block;
	float: left;
	padding: 1em;
	box-sizing: border-box;
}
table.listings tbody tr>*:first-child {
	border-top: 1px solid #ccc;
	border-radius: 1em 1em 0 0;
	padding-top: 0.5em;
}
table.listings tbody tr>*:last-child {
	border-bottom:1px solid #ccc;
	border-radius: 0 0 1em 1em;
	padding-bottom: 0.5em;
}
table.listings tbody tr>* {
	border-left: 1px solid #ccc;
	border-right: 1px solid #ccc;
}
table.listings tbody td, table.listings tbody th {
	display: block;
	padding-left: 1em;
	padding-right: 1em;
	box-sizing: border-box;
}
table.listings tbody td.img {
	padding-left: 2em;
	padding-right: 2em;
	/* Ensures 2em margin either side of image but only 1em either side of name */
}

/*Listings table - individual data*/
th.name {
	font-size: 18pt;
	display: inline;
}
table.listings img {
	display: block;
	width: 200px;
	height: 300px;
	margin-left: auto;
	margin-right: auto;
}
table.listings td, th {
	width: 100%;
	text-align: center;
	vertical-align: middle;
}
table.listings thead {display: none;}
td.fee { color: red; }
td.inquiry>a {
	 font-size: 10pt;
	 text-decoration: none;
  }


/*Listings table - applications pending & closed*/
table.listings tr { order: 1; }
table.listings tr.soon { order: 2; } /* put in middle */
table.listings tr.closed, table.listings tr.pending {
	order: 3; /* put at end */
}
table.listings tr a:hover { text-decoration: none; }
table.listings tr:not(.soon) th.name a { border-bottom: 1pt solid #066; }
table.listings tr:not(.soon) th.name a:visited { border-bottom: 1pt solid #39f; }
table.listings tr:not(.soon) th.name a:hover { border-bottom-width: 1.5pt; }
tr * { background-color: #fff; }
tr.closed *, tr.pending * {	background-color: #ddd;	}

td.fee::before { white-space: pre-line;	}

th.name>*::after {
	content: ' (id#' attr(id) ')';
	font-size: 11pt;
	vertical-align: 10%;
}

/* Status explanations */

<?php
	$classes = array();
	foreach($statuses as $status):
		if($status['explanation']):
			$classes[] = $status['class']; ?>
tr.<?=$status['class']?>>td.fee::before {
	content: "<?=str_replace('"',"\\\"",$status['statustext'].':\A'.str_replace(array("\r\n","\n","\r"),"\\A\\A",$status['explanation']))?>";
}
<?php endif; endforeach;?>

<?=build_selector('tr.',$classes,'>td.fee>*::after')?> {
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
<?=build_selector('tr.',$classes,'>td.fee>*:hover::after')?> {
	background-color: #00f;
	color: #fff;
}
@media print {
	<?=build_selector('tr.',$classes,'>td.fee>*::after')?> { display: none; }
}
<?=build_selector('tr.',$classes,'>td.fee')?> {
	overflow: visible;
	position: relative;
}
<?=build_selector('tr.',$classes,'>td.fee::before')?> {
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
<?=build_selector('tr.',$classes,'>td.fee:hover::before')?> {
	opacity: 0.9;
	transition: all 0.18s ease-out 0.18s;
	z-index: 2;
}
