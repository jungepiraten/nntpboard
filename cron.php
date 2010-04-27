<?php

require_once(dirname(__FILE__)."/config.inc.php");

/**
 * Fuehre hier den Cache-Tausch durch
 *  - Nachrichten aus dem Cache hochladen
 *  - Nachrichten in den Cache herunterladen
 **/
foreach ($config->getGroups() as $group) {
	$connection = $group->getConnection($config->getDataDir());
	// Nur CacheConnections zulassen
	if (!($connection instanceof CacheConnection)) {
		continue;
	}
	// TODO try-catch-Block neu aufbauen
	$directconnection = $group->getDirectConnection();
	try {
		$directconnection->open();
		$connection->open();

		$connection->sendMessages($directconnection);

		$connection->loadMessages($directconnection);

		$connection->close();
		$directconnection->close();
	} catch (Exception $e) {
		echo "<pre>".$e->getMessage()."</pre>";
	}
}

?>
