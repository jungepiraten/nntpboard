<?php

require_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__)."/classes/session.class.php");
$session = new Session($config);

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
$connection = $group->getConnection($session->getAuth());
$connection->open();

$message = $connection->getMessage($messageid);
if ($message === null) {
	die("Nachricht ungueltig!");
}

$attachment = $message->getAttachment($partid);
if ($attachment === null) {
	die("Attachment ungueltig!");
}

$disposition = $attachment->getDisposition();
$filename = $attachment->getFilename();
$charset = $attachment->getCharset();

// see RFC 2616 for these Headers
header("Content-Type: ".$attachment->getMimeType() . (empty($charset) ? "" : "; Charset=".$charset));
header("Content-Length: ".$attachment->getLength());
if (!empty($disposition)) {
	header("Content-Disposition: " . $disposition . (empty($filename) ? "" : "; filename=\"".addslashes($filename)."\""));
}

print($attachment->getContent());

?>
