<?php

require_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__)."/classes/session.class.php");
$session = new Session($config);
$template = $config->getTemplate($session->getAuth());

$boardid = stripslashes($_REQUEST["boardid"]);
$threadid = isset($_REQUEST["threadid"]) ? $config->decodeMessageID(stripslashes($_REQUEST["threadid"])) : null;
$messageid = isset($_REQUEST["messageid"]) ? $config->decodeMessageID(stripslashes($_REQUEST["messageid"])) : null;

$board = $config->getBoard($boardid);
if ($board === null) {
	$template->viewexception(new Exception("Board nicht gefunden!"));
}

$connection = $board->getConnection($session->getAuth());
if ($connection === null) {
	$template->viewexception(new Exception("Board enthaelt keine Group!"));
}

/* Thread laden */
// Sobald die Verbindung geoeffnet ist, beginnen wir einen Kritischen Abschnitt!
$connection->open();
$group = $connection->getGroup();
$connection->close();
if ($threadid === null && $messageid !== null) {
	$message = $group->getMessage($messageid);
	if (!($message instanceof Message)) {
		$template->viewexception(new Exception("Message konnte nicht zugeordnet werden."));
	}
	$thread = $group->getThread($messageid);
	$template->viewmessage($board, $thread, $message, $board->mayPost($session->getAuth()), $board->mayAcknowledge($session->getAuth()));
}

$thread = $group->getThread($threadid);
if (!($thread instanceof Thread)) {
	$template->viewexception(new Exception("Thread nicht gefunden!"));
}

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
$connection->close();
if (!is_array($messages) || count($messages) < 1) {
	$template->viewexception(new Exception("Thread ungueltig!"));
}

$session->getAuth()->markReadThread($thread);
$template->viewthread($board, $thread, $page, $pages, $messages, $board->mayPost($session->getAuth()), $board->mayAcknowledge($session->getAuth()));

?>
