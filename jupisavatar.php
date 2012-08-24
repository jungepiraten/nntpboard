<?php

define("CACHEPERIOD", 24 * 60 * 60);
define("THUMBWIDTH", 100);
define("THUMBHEIGHT", 140);

// Funktion zum Laden eines Bildes aus der Wikiseite
function getImage($page) {
	$link = "http://wiki.junge-piraten.de/w/api.php?action=query&prop=images&format=xml&titles=" . urlencode($page);
	$blacklist = array("Politischer Kompass.svg", "Edit add.svg", "Zebramitstern.png");

	$xml = file_get_contents($link);

	preg_match_all('#<im\s+ns="6"\s+title="Datei:([^"]*(\.jpg|\.jpeg|\.gif|\.png))"[\s/]*>#Ui', $xml, $matches, PREG_SET_ORDER);

	foreach ($matches as $match) {
		$file = $match[1];
		if (!in_array($file, $blacklist)) {
			return "http://wiki.junge-piraten.de/wiki/Spezial:Dateipfad/" . urlencode(str_replace(" ", "_", $file));
		}
	}

	return null;
}

if (isset($_REQUEST["name"])) {
	$name = stripslashes($_REQUEST["name"]);
	$cachename = "avatarcache/" . md5($name);

	// Wenn unser Cachebild nicht zu alt ist, benutze es
	if (file_exists($cachename . ".png") && filemtime($cachename . ".png") > time() - CACHEPERIOD) {
		header("Content-Type: image/png");
		readfile($cachename . ".png");
		exit;
	}

	// Ladereihenfolge: Zuerst Benutzer/Avatar - falls dieses nicht existiert, probiere die Regulaere Benutzerseite
	$image = getImage("Benutzer:" . ucfirst($name) . "/Avatar");
	if ($image === null) {
		$image = getImage("Benutzer:" . ucfirst($name));
	}
} else {
	$cachename = "avatarcache/" . $_REQUEST["gravatar-hash"];
}

if ($image === null) {
	Header("Location: https://secure.gravatar.com/avatar/" . $_REQUEST["gravatar-hash"] . "?s=" . min(THUMBWIDTH, THUMBHEIGHT) . "&default=mm");
	exit(0);
}

$meta = getimagesize($image);
switch ($meta["mime"]) {
case "image/jpg":
case "image/jpeg":
	$img = ImageCreateFromJPEG($image);
	break;
case "image/gif":
	$img = ImageCreateFromGIF($image);
	break;
case "image/png":
	$img = ImageCreateFromPNG($image);
	break;
}
if (!is_resource($img)) {
	die("Fail!");
}
$origw = ImageSx($img);
$origh = ImageSy($img);

if ($origw > THUMBWIDTH || $origh > THUMBHEIGHT) {
	if ($origw * THUMBHEIGHT > $origh * THUMBWIDTH) {
		$thumbw = THUMBWIDTH;
		$thumbh = $origh * (THUMBWIDTH / $origw);
	} else {
		$thumbw = $origw * (THUMBHEIGHT / $origh);
		$thumbh = THUMBHEIGHT;
	}

	$thumb = ImageCreateTrueColor($thumbw, $thumbh);
	ImageAlphaBlending($thumb, false);
	ImageSaveAlpha($thumb, true);
	ImageCopyResampled($thumb, $img, 0, 0, 0, 0, $thumbw, $thumbh, $origw, $origh);
} else {
	$thumb = $img;
	ImageAlphaBlending($thumb, false);
	ImageSaveAlpha($thumb, true);
}

ImagePNG($thumb, $cachename . ".png");
ImageDestroy($thumb);
header("Content-Type: image/png");
readfile($cachename . ".png");

?>
