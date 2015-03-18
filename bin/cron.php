#!/usr/bin/env php
<?php

require_once(dirname(__FILE__)."/../config.inc.php");

if ($config->isCronRunning()) {
	die("Cronjob already running.");
}
$config->markCronRunning();

/**
 * Fuehre hier den Cache-Tausch durch
 *  - Nachrichten in den Cache herunterladen
 **/
foreach ($config->getBoardIDs() as $boardid) {
	$cache = $config->getBoard($boardid)->getConnection();

	// Nur bei CacheConnections macht das wirklich Sinn ...
	if (!($cache instanceof AbstractCacheConnection)) {
		continue;
	}

	try {
		// Benutze keine Authentifikation
		$cache->open(NULL);

		// Versuche neue Nachrichten zu ergattern
		$cache->updateCache();

		$cache->close();
	} catch (Exception $e) {
		echo "<pre>".$e->getMessage()."\n" . $e->getTraceAsString() . "</pre>\n";
	}
}

$config->markCronFinished();

?>
