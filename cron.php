<?php

require_once(dirname(__FILE__)."/config.inc.php");

/**
 * Fuehre hier den Cache-Tausch durch
 *  - Nachrichten aus dem Cache hochladen
 *  - Nachrichten in den Cache herunterladen
 **/
foreach ($config->getGroups() as $group) {
	// Benutze keinen Auth ...
	$cache = $group->getConnection(null);
	
	// Nur bei CacheConnections macht das wirklich Sinn ...
	if (!($cache instanceof CacheConnection)) {
		continue;
	}

	try {
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
		$cache->loadMessages();

		// Versuche lokale Nachrichten zu posten
		/* WICHTIG: Diese Nachrichten werden mit dem Auth-Parameter "null"
		 *          verschickt - das kann, muss aber nicht funktionieren :P
		 */
		$cache->sendMessages();
		
		$cache->close();
	} catch (Exception $e) {
var_dump($e);
		echo "<pre>".$e->getMessage()."</pre>";
	}
}

?>
