<?php

require_once(dirname(__FILE__)."/config.inc.php");

/**
 * Fuehre hier den Cache-Tausch durch
 *  - Nachrichten aus dem Cache hochladen
 *  - Nachrichten in den Cache herunterladen
 **/
foreach ($config->getGroups() as $group) {
	$cache = $group->getConnection($config->getDataDir(), null, Group::CONNECTION_CACHE);
	// Nur CacheConnections zulassen
	if (!($cache instanceof CacheConnection)) {
		continue;
	}

	$connection = $group->getConnection($config->getDataDir(), null, Group::CONNECTION_DIRECT);
	try {
		$connection->open();
		$cache->open();

		// Versuche lokale Nachrichten zu posten
		try {
			$cache->sendMessages($connection);
		} catch (Exception $e) {
			echo "<pre>" . $e->getMessage() . "</pre>";
		}

		// Versuche neue Nachrichten zu ergattern
		try {
			$cache->loadMessages($connection);
		} catch (Exception $e) {
			echo "<pre>" . $e->getMessage() . "</pre>";
		}

		$cache->close();
		$connection->close();
	} catch (Exception $e) {
		echo "<pre>".$e->getMessage()."</pre>";
	}
}

?>
