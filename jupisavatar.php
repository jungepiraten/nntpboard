<?php

define("CACHEPERIOD", 24 * 60 * 60);
define("THUMBWIDTH", 100);
define("THUMBHEIGHT", 100);

$name = stripslashes($_REQUEST["name"]);
$cachename = "avatarcache/" . md5($name);

// Wenn unser Cachebild nicht zu alt ist, benutze es
if (file_exists($cachename . ".png") && filemtime($cachename . ".png") > time() - CACHEPERIOD) {
	header("Content-Type: image/png");
	readfile($cachename . ".png");
	exit;
}

// Funktion zum Laden eines Bildes aus der Wikiseite
function getImage($page) {
	$link = "http://wiki.junge-piraten.de/w/api.php?action=query&prop=images&&format=xml&titles=" . urlencode($page);
	$blacklist = array("Politischer Kompass.svg", "Edit add.svg", "Zebramitstern.png");

	$xml = file_get_contents($link);

	preg_match_all('#<im\s+ns="6"\s+title="Datei:(.*(\.jpg|\.jpeg|\.gif|\.png))"[\s/]*>#Ui', $xml, $matches, PREG_SET_ORDER);

	foreach ($matches as $match) {
		$file = $match[1];
		if (!in_array($file, $blacklist)) {
			return "http://wiki.junge-piraten.de/wiki/Spezial:Dateipfad/" . urlencode(str_replace(" ", "_", $file));
		}
	}

	return null;
}

// Ladereihenfolge: Zuerst Benutzer/Avatar - falls dieses nicht existiert, probiere die Regulaere Benutzerseite
$image = getImage("Benutzer:" . ucfirst($name) . "/Avatar");
if ($image === null) {
	$image = getImage("Benutzer:" . ucfirst($name));
}

if ($image === null) {
	$image = "images/genericperson.png";
}

if (substr($image, -5) == ".jpeg" || substr($image, -4) == ".jpg") {
	$img = ImageCreateFromJPEG($image);
} else if (substr($image, -4) == ".gif") {
	$img = ImageCreateFromGIF($image);
} else if (substr($image, -4) == ".png") {
	$img = ImageCreateFromPNG($image);
}
if (!is_resource($img)) {
	die("Fail!");
}
$origw = ImageSx($img);
$origh = ImageSy($img);
if ($origw > THUMBWIDTH || $origh > THUMBHEIGHT) {
	if ($origw > $origh) {
		$thumbw = THUMBWIDTH;
		$thumbh = $origh * (THUMBWIDTH / $origw);
	} else if ($origw < $origh) {
		$thumbw = $origw * (THUMBHEIGHT / $origh);
		$thumbh = THUMBHEIGHT;
	}
	$thumb = ImageCreateTrueColor($thumbw, $thumbh);
	ImageCopyResampled($thumb, $img, 0, 0, 0, 0, $thumbw, $thumbh, $origw, $origh);
	$transparentcolor = ImageColorTransparent($img);
	if ($transparentcolor > 0) {
		$t = ImageColorsForIndex($img, $transparentcolor);
		$thumbtransparent = ImageColorClosest($thumb, $t[0], $t[1], $t[2]);
		ImageColorTransparent($thumb, $thumbtransparent);
	}
} else {
	$thumb = $img;
}
ImagePNG($thumb, $cachename . ".png");
ImageDestroy($thumb);
header("Content-Type: image/png");
readfile($cachename . ".png");

?>
