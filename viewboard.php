<?php

require_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__)."/classes/session.class.php");
$session = new Session($config);
$template = $config->getTemplate($session->getAuth());

$id = !empty($_REQUEST["id"]) ? stripslashes($_REQUEST["id"]) : null;

$board = $config->getBoard($id);
if ($board === null) {
	$template->viewexception(new Exception("Board nicht gefunden!"));
}

$group = $board->getGroup();
if ($group !== null) {
	$connection = $group->getConnection($config->getDataDir(), $session->getAuth());
	$connection->open();
	$threads = $connection->getThreads();
	$connection->close();
	
	$template->viewboard($board, $group, $threads, $group->mayPost($session->getAuth()));
} else {
	$template->viewboard($board, $group);
}


?>
