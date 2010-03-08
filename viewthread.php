<?php

require_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__)."/smarty.inc.php");
$smarty = new ViewThreadSmarty($config);

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
$group->load();

if ($messageid !== null) {
	$message = $group->getMessage($messageid);
	if ($message === null) {
		die("Message konnte nicht zugeordnet werden.");
	}
	$threadid = $message->getThreadID();
}
$thread = $group->getThread($threadid);
if ($thread === null) {
	die("Thread nicht gefunden!");
}
if ($message !== null) {
	$smarty->viewmessage($board, $thread, $message);
}
$messages = $group->getThreadMessages($thread->getThreadID());
if (!is_array($messages) || count($messages) < 1) {
	die("Thread ungueltig!");
}

$smarty->viewthread($board, $thread, $messages);

?>
