<?php

require_once(dirname(__FILE__)."/config.inc.php");

$boardid = stripslashes($_REQUEST["boardid"]);
$messageid = stripslashes($_REQUEST["messageid"]);
$partid = stripslashes($_REQUEST["partid"]);

$board = $config->getBoard($boardid);
if ($board === null) {
	die("Board nicht gefunden!");
}

$group = $board->getGroup();
if ($group === null) {
	die("Board enthaelt keine Group!");
}
$group->load();

$message = $group->getMessage($messageid);
if ($message === null) {
	die("Nachricht ungueltig!");
}

$part = $message->getBodyPart($partid);
if ($part === null) {
	die("BodyPart ungueltig!");
}

$disposition = $part->getDisposition();
$filename = $part->getFilename();
$charset = $part->getCharset();

header("Content-Type: ".$part->getMimeType() . (empty($charset) ? "" : "; Charset=".$charset));
if (!empty($disposition)) {
	// TODO "quoted string" http://www.faqs.org/rfcs/rfc2616
	header("Content-Disposition: " . $disposition . (empty($filename) ? "" : "; filename=".$filename));
}

print($part->getText());

?>
