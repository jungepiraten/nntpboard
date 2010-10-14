<?php

require_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__)."/classes/session.class.php");
require_once(dirname(__FILE__)."/classes/cancel.class.php");
$session = new Session($config);
$template = $config->getTemplate($session->getAuth());

function recurseMarkRead($board, $auth) {
	if ($board->hasGroup()) {
		$auth->markReadGroup($board);
	}
	foreach ($board->getChilds() as $child) {
		recurseMarkRead($child, $auth);
	}
}

if (isset($_REQUEST["markread"])) {
	$boardid = is_numeric($_REQUEST["readall"]) ? intval($_REQUEST["readall"]) : null;
	$board = $config->getBoard($boardid);
	recurseMarkRead($board, $session->getAuth());
}

if (isset($_SERVER["HTTP_REFERRER"])) {
	header("Location: " . $_SERVER["HTTP_REFERRER"]);
}

?>
