<?php

require_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__)."/classes/session.class.php");

$session = new Session($config);
$template = $config->getTemplate($session->getAuth());

$boardid = $_REQUEST["boardid"];
$threadid = isset($_REQUEST["threadid"]) ? $config->decodeMessageID($_REQUEST["threadid"]) : null;
$messageid = isset($_REQUEST["messageid"]) ? $config->decodeMessageID($_REQUEST["messageid"]) : null;

try {
	$board = $config->getBoard($boardid);

	if (!$board->mayRead($session->getAuth())) {
		throw new Exception("Keine Berechtigung!");
	}

	$connection = $board->getConnection();
	if ($connection === null) {
		throw new Exception("Board enthaelt keine Group!");
	}

	/* Thread laden */
	// Sobald die Verbindung geoeffnet ist, beginnen wir einen Kritischen Abschnitt!
	$connection->open($session->getAuth());
	$group = $connection->getGroup();
	$connection->close();

	if ($threadid === null && $messageid !== null) {
		$message = $group->getMessage($messageid);
		if (!($message instanceof Message)) {
			throw new Exception("Message konnte nicht zugeordnet werden.");
		}
		$thread = $group->getThread($messageid);
		$template->viewmessage($board, $thread, $message, $board->mayPost($session->getAuth()), $board->mayAcknowledge($session->getAuth()));
	}

	$thread = $group->getThread($threadid);

	// Erzwinge mindestens eine Seite
	$pages = max(ceil($thread->getMessageCount() / $config->getMessagesPerPage()), 1);
	$page = 0;
	if (isset($_REQUEST["page"])) {
		$page = intval($_REQUEST["page"]);
	}
	// Vorsichtshalber erlauben wir nur Seiten, auf dennen auch Nachrichten stehen
	if ($page < 0 || $page > $pages) {
		$page = 0;
	}

	// Nachrichten laden
	$messageids = array_slice($thread->getMessageIDs(), $page * $config->getMessagesPerPage(), $config->getMessagesPerPage());
	$messages = array();

	foreach ($messageids AS $messageid) {
		$message = array();
		$message["message"] = $group->getMessage($messageid);
		$message["acknowledges"] = array();
		$acknowledgeids = $group->getAcknowledgeMessageIDs($messageid);
		foreach ($acknowledgeids as $acknowledgeid) {
			$message["acknowledges"][$acknowledgeid] = $group->getAcknowledge($acknowledgeid);
		}
		$messages[] = $message;
	}
	// $message will now contain the last message on that page

	if (!is_array($messages) || count($messages) < 1) {
		throw new Exception("Thread ungueltig!");
	}

	// order is important: the template will check if the message is unread
	$template->viewthread($board, $thread, $page, $pages, $messages, $board->mayPost($session->getAuth()), $board->mayAcknowledge($session->getAuth()));
	$session->getAuth()->markReadThread($thread, $message["message"]);
} catch (Exception $e) {
	$template->viewexception($e);
}

// Not sure if needed, as connection gets closed in try-block
//$connection->close();

?>
