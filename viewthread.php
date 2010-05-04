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

$group = $board->getGroup();
if ($group === null) {
	$template->viewexception(new Exception("Board enthaelt keine Group!"));
}

$connection = $group->getConnection($config->getDataDir(), $session->getAuth());
/* Sobald die Verbindung geoeffnet ist, beginnen wir einen Kritischen Abschnitt! */
$connection->open();

if ($messageid !== null) {
	$message = $connection->getMessage($messageid);
	if ($message === null) {
		// viewexception beendet das Script
		$connection->close();
		$template->viewexception(new Exception("Message konnte nicht zugeordnet werden."));
	}
	$threadid = $message->getThreadID();
}
$thread = $connection->getThread($threadid);
$connection->close();

if ($thread === null) {
	$template->viewexception(new Exception("Thread nicht gefunden!"));
}

if ($message !== null) {
	$template->viewmessage($board, $thread, $message, $group->mayPost($session->getAuth()));
}
$messages = $thread->getMessages($connection);
if (!is_array($messages) || count($messages) < 1) {
	$template->viewexception(new Exception("Thread ungueltig!"));
}

$template->viewthread($board, $thread, $messages, $group->mayPost($session->getAuth()));

?>
