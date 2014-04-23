<?php
require_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__)."/classes/session.class.php");
require_once(dirname(__FILE__)."/classes/cancel.class.php");

$session = new Session($config);
$template = $config->getTemplate($session->getAuth());
$boardid = $_REQUEST["boardid"];
$messageid = isset($_REQUEST["messageid"]) ? $config->decodeMessageID($_REQUEST["messageid"]) : null;

$board = $config->getBoard($boardid);
if ($board === null) {
	$template->viewexception(new Exception("Board nicht gefunden!"));
	exit;
}

$connection = $board->getConnection();
if ($connection === null) {
	$template->viewexception(new Exception("Board enthaelt keine Group!"));
	exit;
}

/* Thread laden */
// Sobald die Verbindung geoeffnet ist, beginnen wir einen Kritischen Abschnitt!
$connection->open($session->getAuth());
$group = $connection->getGroup();
$connection->close();

$message = $group->getMessage($messageid);
$thread = $group->getThread($messageid);

if (!($message instanceof Message)) {
	$template->viewexception(new Exception("Message konnte nicht zugeordnet werden."));
	exit;
}

if (!$session->getAuth()->mayCancel($message)) {
	$template->viewexception(new Exception("Keine Berechtigung!"));
	exit;
}

// TODO Zustimmungs-Posts canceln?
$cancelid = $config->generateMessageID();
// TODO autor-input?

if ($session->getAuth()->isAnonymous()) {
	$author = new Address(trim($_REQUEST["user"]), trim($_REQUEST["email"]));
} else {
	$author = $session->getAuth()->getAddress();
}

$cancel = new Cancel($cancelid, $messageid, time(), $author, $wertung);
$connection->open($session->getAuth());
$resp = $connection->postCancel($cancel, $message);
$connection->close();

if ($resp === "m") {
	$template->viewcancelmoderated($board, $thread, $message, $cancel);
} else {
	$template->viewcancelsuccess($board, $thread, $message, $cancel);
}
?>
