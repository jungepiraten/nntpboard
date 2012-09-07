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

if (!$board->mayRead($session->getAuth())) {
	$template->viewexception(new Exception("Keine Berechtigung!"));
}

$connection = $board->getConnection();
if ($connection !== null) {
	$connection->open($session->getAuth());
	$group = $connection->getGroup();
	$connection->close();

	// Erzwinge mindestens eine Seite
	$pages = max(ceil($group->getThreadCount() / $config->getThreadsPerPage()), 1);
	$page = 0;

	if (isset($_REQUEST["page"])) {
		$page = intval($_REQUEST["page"]);
	}

	// Vorsichtshalber erlauben wir nur Seiten, auf dennen auch Nachrichten stehen
	if ($page < 0 || $page > $pages) {
		$page = 0;
	}

	$threads = array();
	/** getThreadIDs() gibt alle ThreadIDs in der Reihenfolge Alt => Neu
	 * zurueck. In der Forendarstellung wollen wir die neuesten x Threads
	 * von Neu => Alt. */
	$threadids = array_slice(array_reverse($group->getThreadIDs()), $page * $config->getThreadsPerPage(), $config->getThreadsPerPage());
	foreach ($threadids AS $threadid) {
		$thread = $group->getThread($threadid);
		if ($thread != NULL) {
			$threads[] = $thread;
		}
	}

	$template->viewboard($board, $group, $page, $pages, $threads, $board->mayPost($session->getAuth()), $board->mayAcknowledge($session->getAuth()));
} else {
	$template->viewboard($board, null);
}
?>
