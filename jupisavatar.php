<?php

$name = stripslashes($_REQUEST["name"]);

$link = "http://wiki.junge-piraten.de/w/api.php?action=query&prop=images&&format=xml&titles=Benutzer:" . urlencode($name);
$xml = file_get_contents($link);

preg_match_all('#<im\s+ns="6"\s+title="Datei:(.*)"[\s/]*>#Ui', $xml, $matches, PREG_SET_ORDER);

foreach ($matches as $match) {
	$file = $match[1];
	if ($file != "Politischer Kompass.svg") {
		header("Location: http://wiki.junge-piraten.de/wiki/Spezial:Dateipfad/" . urlencode(str_replace(" ", "_", $file)));
		exit;
	}
}

header("Location: images/genericperson.jpg");

?>
