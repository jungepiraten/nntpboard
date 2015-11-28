<?php

require_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__)."/classes/session.class.php");

$session = new Session($config);
$template = $config->getTemplate($session->getAuth());

try {
	if ($config->getIndexer() == null) {
		throw new Exception("Search not available: No indexer used.");
	}

	if (isset($_REQUEST["term"])) {
		$term = $_REQUEST["term"];
		$_results = $config->getIndexer()->getResults($term);

		// Erzwinge mindestens eine Seite
		$pages = max(ceil(count($_results) / $config->getMessagesPerPage()), 1);
		$page = 0;
		if (isset($_REQUEST["page"])) {
			$page = intval($_REQUEST["page"]);
		}
		// Vorsichtshalber erlauben wir nur Seiten, auf dennen auch Nachrichten stehen
		if ($page < 0 || $page > $pages) {
			$page = 0;
		}

		$_results = array_slice($_results, $page * $config->getMessagesPerPage(), $config->getMessagesPerPage());
		$results = array();

		foreach ($_results as $result) {
			$board = $config->getBoard($result->getBoardID());

			if (!$board->mayRead($session->getAuth())) {
				continue;
			}

			$connection = $board->getConnection();
			if ($connection === null) {
				continue;
			}

			// Sobald die Verbindung geoeffnet ist, beginnen wir einen Kritischen Abschnitt!
			$connection->open($session->getAuth());
			$group = $connection->getGroup();
			$connection->close();

			$message = $group->getMessage($result->getMessageID());
			if (!($message instanceof Message)) {
				// TODO remove messageid from index
				continue;
			}

			$results[] = array(
				"board" => $board,
				"message" => $message
			);
		}

		$template->viewsearchresults($page, $pages, $term, $results);
	} else {
		$template->viewsearchform();
	}
} catch (Exception $e) {
	$template->viewexception($e);
}

?>
