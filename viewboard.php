<?php

require_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__)."/smarty.inc.php");
$smarty = new ViewBoardSmarty($config);

$id = !empty($_REQUEST["id"]) ? stripslashes($_REQUEST["id"]) : null;

$board = $config->getBoard($id);
if ($board === null) {
	die("Board nicht gefunden!");
}

$group = $board->getGroup();
if ($group !== null) {
	$connection = $group->getConnection($config->getDataDir());
	$connection->open();
	$threads = $connection->getThreads();
} else {
	$threads = null;
}

$smarty->viewboard($board, $group, $threads);

?>
