<?php

require_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__)."/smarty.inc.php");
$smarty = new ViewThreadSmarty($config);

$boardid = stripslashes($_REQUEST["boardid"]);
$threadid = stripslashes($_REQUEST["threadid"]);

$board = $config->getBoard($boardid);
if ($board === null) {
	die("Board nicht gefunden!");
}

$group = $board->getGroup();
if ($group === null) {
	die("Board enthaelt keine Group!");
}
$group->load();

$thread = $group->getThread($threadid);
$messages = $group->getThreadMessages($threadid);
if (!is_array($messages) || count($messages) < 1) {
	die("Thread ungueltig!");
}

$smarty->assign("board", $board);
$smarty->assign("thread", $thread);
$smarty->assign("messages", $messages);

$smarty->display();

?>
