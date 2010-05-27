<?php

require_once(dirname(__FILE__)."/config.inc.php");

/**
 * Fuehre hier den Cache-Tausch durch
 *  - Nachrichten in den Cache herunterladen
 **/
$start = microtime(true);
foreach ($config->getBoardIDs() as $boardid) {
	// Benutze keine Authentifikation
	$cache = $config->getBoard($boardid)->getConnection(NULL);
	
	// Nur bei CacheConnections macht das wirklich Sinn ...
	if (!($cache instanceof AbstractCacheConnection)) {
		continue;
	}

	try {
		$cache->open();

		// Versuche neue Nachrichten zu ergattern
		$cache->updateCache();
		
		$cache->close();
	} catch (Exception $e) {
		echo "<pre>".$e->getMessage()."</pre>";
	}
}
var_dump(microtime(true) - $start);

?>
