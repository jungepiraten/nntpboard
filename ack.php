<?php

require_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__)."/classes/session.class.php");

$session = new Session($config);
$template = $config->getTemplate($session->getAuth());

$boardid = $_REQUEST["boardid"];
$messageid = isset($_REQUEST["messageid"]) ? $config->decodeMessageID($_REQUEST["messageid"]) : null;
$wertung = isset($_REQUEST["wertung"]) ? intval($_REQUEST["wertung"]) : +1;

try {
	$board = $config->getBoard($boardid);

	if (!$board->mayAcknowledge($session->getAuth())) {
		throw new Exception("Keine Berechtigung!");
	}

	$connection = $board->getConnection();
	if ($connection === null) {
		throw new Exception("Board enthaelt keine Group!");
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
		throw new Exception("Message konnte nicht zugeordnet werden.");
		exit;
	}

	// TODO mehrfache zustimmungen?
	$ackid = $config->generateMessageID();
	// TODO autor-input?
	$autor = $session->getAuth()->isAnonymous()
		? new Address(trim($_REQUEST["user"]), trim($_REQUEST["email"]))
		: $session->getAuth()->getAddress();
	$ack = new Acknowledge($ackid, $messageid, time(), $autor, $wertung);

	$connection->open($session->getAuth());
	$resp = $connection->postAcknowledge($ack, $message);
	$connection->close();
	if ($resp === "m") {
		$template->viewacknowledgemoderated($board, $thread, $message, $ack);
	} else {
		$template->viewacknowledgesuccess($board, $thread, $message, $ack);
	}
} catch (Exception $e) {
	$template->viewexception($e);
}

?>
