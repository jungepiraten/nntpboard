<?php

require_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__)."/classes/session.class.php");
$session = new Session($config);
$template = $config->getTemplate($session->getAuth());

$boardid = !empty($_REQUEST["boardid"]) ? stripslashes($_REQUEST["boardid"]) : null;

$board = $config->getBoard($boardid);
if ($board === null) {
	$template->viewexception(new Exception("Board nicht gefunden!"));
}

$connection = $board->getConnection($session->getAuth());
if ($connection !== null) {
	$connection->open();
	$group = $connection->getGroup();
	$connection->close();

	$threads = array();
	foreach ($group->getThreadIDs() AS $threadid) {
		$threads[] = $group->getThread($threadid);
	}

	if (!function_exists("cmpThreads")) {
		function cmpThreads($a, $b) {
			return $b->getLastPostDate() - $a->getLastPostDate();
		}
	}
	uasort($threads, cmpThreads);
	
	$template->viewboard($board, $group, $threads, $board->mayPost($session->getAuth()));
} else {
	$template->viewboard($board, $group);
}

?>
