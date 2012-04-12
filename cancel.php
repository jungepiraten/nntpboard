<?php

require_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__)."/classes/session.class.php");
require_once(dirname(__FILE__)."/classes/cancel.class.php");
$session = new Session($config);
$template = $config->getTemplate($session->getAuth());

$boardid = stripslashes($_REQUEST["boardid"]);
$messageid = isset($_REQUEST["messageid"]) ? $config->decodeMessageID(stripslashes($_REQUEST["messageid"])) : null;

$board = $config->getBoard($boardid);
if ($board === null) {
	$template->viewexception(new Exception("Board nicht gefunden!"));
}

$connection = $board->getConnection();
if ($connection === null) {
	$template->viewexception(new Exception("Board enthaelt keine Group!"));
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
}

if (!$session->getAuth()->mayCancel($message)) {
	$template->viewexception(new Exception("Keine Berechtigung!"));
}

// TODO Zustimmungs-Posts canceln?

$cancelid = $config->generateMessageID();
// TODO autor-input?
$autor = $session->getAuth()->isAnonymous()
	? new Address(trim(stripslashes($_REQUEST["user"])), trim(stripslashes($_REQUEST["email"])))
	: $session->getAuth()->getAddress();
$cancel = new Cancel($cancelid, $messageid, time(), $autor, $wertung);

$connection->open();
$resp = $connection->postCancel($cancel, $message);
$connection->close();
if ($resp == "m") {
	$template->viewcancelmoderated($board, $thread, $message, $cancel);
} else {
	$template->viewcancelsuccess($board, $thread, $message, $cancel);
}

?>
