<?php
require_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__)."/classes/session.class.php");

$session = new Session($config);
$boardid = stripslashes($_REQUEST["boardid"]);
$messageid = $config->decodeMessageID(stripslashes($_REQUEST["messageid"]));
$partid = stripslashes($_REQUEST["partid"]);

$board = $config->getBoard($boardid);
if ($board === null) {
	die("Board nicht gefunden!");
}

$connection = $board->getConnection();
if ($connection === null) {
	die("Board enthaelt keine Group!");
}

$connection->open($session->getAuth());
$group = $connection->getGroup();
$connection->close();

$message = $group->getMessage($messageid);
if ($message === false) {
	$attachment = $session->getAttachment($partid);
} else {
	$attachment = $message->getAttachment($partid);
}

if ($attachment === null) {
	die("Attachment ungueltig!");
}

$disposition = $attachment->getDisposition();
$filename = $attachment->getFilename();

// Fix for images
if (preg_match("$^image/$", $attachment->getMimeType())) {
	$disposition = "inline";
}

// see RFC 2616 for these Headers
header("Content-Type: ".$attachment->getMimeType());
header("Content-Length: ".$attachment->getLength());

if (!empty($disposition)) {
	header("Content-Disposition: " . $disposition . (empty($filename) or $disposition == "inline" ? "" : "; filename=\"".addslashes($filename)."\""));
}

print($attachment->getContent());
?>
