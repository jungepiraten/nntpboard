<?php

require_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__)."/classes/session.class.php");
require_once(dirname(__FILE__)."/classes/cancel.class.php");

$session = new Session($config);
$template = $config->getTemplate($session->getAuth());

function recurseMarkRead($board, $auth) {
	if ($board->hasThreads()) {
		$connection = $board->getConnection();
		$connection->open($auth);
		$auth->markReadGroup($connection->getGroup());
		$connection->close();
	}
	foreach ($board->getSubBoardIDs() as $boardid) {
		recurseMarkRead($board->getSubBoard($boardid), $auth);
	}
}

if (isset($_REQUEST["markread"])) {
	$boardid = is_numeric($_REQUEST["markread"]) ? intval($_REQUEST["markread"]) : null;
	$board = $config->getBoard($boardid);
	recurseMarkRead($board, $session->getAuth());
}

if (isset($_SERVER["HTTP_REFERER"])) {
	header("Location: " . $_SERVER["HTTP_REFERER"]);
} else {
	header("Location: /");
}

?>
