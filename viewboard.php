<?php

require_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__)."/smarty.inc.php");
$smarty = new ViewBoardSmarty($config);

$id = !empty($_REQUEST["id"]) ? stripslashes($_REQUEST["id"]) : null;

$smarty->assign("board", $board = $config->getBoard($id));
if ($board === null) {
	die("Board nicht gefunden!");
}

if ($group = $board->getGroup()) {
	$group->load();
	$smarty->assign("threads", $group->getThreads());
}

$smarty->display();

?>
