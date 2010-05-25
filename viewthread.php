<?php

require_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__)."/classes/session.class.php");
$session = new Session($config);
$template = $config->getTemplate($session->getAuth());

$boardid = stripslashes($_REQUEST["boardid"]);
$threadid = isset($_REQUEST["threadid"]) ? stripslashes($_REQUEST["threadid"]) : null;
$messageid = isset($_REQUEST["messageid"]) ? stripslashes($_REQUEST["messageid"]) : null;

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
	if ($message === null) {
		// viewexception beendet das Script
		$connection->close();
		$template->viewexception(new Exception("Message konnte nicht zugeordnet werden."));
	}
	$thread = $group->getThread($messageid);
	$template->viewmessage($board, $thread, $message, $board->mayPost($session->getAuth()));
}

$thread = $group->getThread($threadid);
if ($thread === null) {
	$template->viewexception(new Exception("Thread nicht gefunden!"));
}

// Nachrichten laden
$messages = array();
foreach ($thread->getMessageIDs() AS $messageid) {
	$messages[] = $group->getMessage($messageid);
}
$connection->close();
if (!is_array($messages) || count($messages) < 1) {
	$template->viewexception(new Exception("Thread ungueltig!"));
}

$session->getAuth()->markReadThread($thread);
$template->viewthread($board, $thread, $messages, $board->mayPost($session->getAuth()));

?>
