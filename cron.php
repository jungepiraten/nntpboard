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

		/**
		 * Die Reihenfolge ist wichtig wegen der Queue im Cache!
		 * Zuerst wird der Cache bereinigt (dabei werden Nachrichten,
		 *  die schon gepostet wurden als solche Markiert und aus der
		 *  Queue gestrichen)
		 * Dannach werden Nachrichten, die noch immer in der Queue stehen
		 *  hochgeladen.
		 **/

		// Versuche neue Nachrichten zu ergattern
		try {
			$cache->loadMessages($connection);
		} catch (Exception $e) {
			echo "<pre>" . $e->getMessage() . "</pre>";
		}

		// Versuche lokale Nachrichten zu posten
		try {
			$cache->sendMessages($connection);
		} catch (Exception $e) {
			echo "<pre>" . $e->getMessage() . "</pre>";
		}
		
		$cache->close();
		$connection->close();
	} catch (Exception $e) {
var_dump($e);
		echo "<pre>".$e->getMessage()."</pre>";
	}
}

?>
