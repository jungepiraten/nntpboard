<?php

require_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__)."/classes/smarty.class.php");
require_once(dirname(__FILE__)."/classes/session.class.php");
$session = new Session($config);
$smarty = new ViewBoardSmarty($config, $session->getAuth());

$id = !empty($_REQUEST["id"]) ? stripslashes($_REQUEST["id"]) : null;

$board = $config->getBoard($id);
if ($board === null) {
	die("Board nicht gefunden!");
}

$group = $board->getGroup();
if ($group !== null) {
	$connection = $group->getConnection($config->getDataDir(), $session->getAuth());
	$connection->open();
	$smarty->viewboard($board, $group, $connection->getThreads(), $connection->mayPost());
	$connection->close();
} else {
	$smarty->viewboard($board, $group);
}


?>
