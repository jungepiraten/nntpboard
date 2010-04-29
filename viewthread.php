<?php

require_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__)."/classes/smarty.class.php");
require_once(dirname(__FILE__)."/classes/session.class.php");
$session = new Session($config);
$smarty = new ViewThreadSmarty($config, $session->getAuth());

$boardid = stripslashes($_REQUEST["boardid"]);
$threadid = isset($_REQUEST["threadid"]) ? stripslashes($_REQUEST["threadid"]) : null;
$messageid = isset($_REQUEST["messageid"]) ? stripslashes($_REQUEST["messageid"]) : null;

$board = $config->getBoard($boardid);
if ($board === null) {
	die("Board nicht gefunden!");
}

$group = $board->getGroup();
if ($group === null) {
	die("Board enthaelt keine Group!");
}
$connection = $group->getConnection($config->getDataDir(), $session->getAuth());
$connection->open();

if ($messageid !== null) {
	$message = $connection->getMessage($messageid);
	if ($message === null) {
		die("Message konnte nicht zugeordnet werden.");
	}
	$threadid = $message->getThreadID();
}
$thread = $connection->getThread($threadid);
if ($thread === null) {
	die("Thread nicht gefunden!");
}

if ($message !== null) {
	$smarty->viewmessage($board, $thread, $message, $group->mayPost($session->getAuth()));
}
$messages = $thread->getMessages($connection);
if (!is_array($messages) || count($messages) < 1) {
	die("Thread ungueltig!");
}

$smarty->viewthread($board, $thread, $messages, $group->mayPost($session->getAuth()));

$connection->close();

?>
