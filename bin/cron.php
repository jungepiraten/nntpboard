#!/usr/bin/env php
<?php

require_once(dirname(__FILE__)."/../config.inc.php");

if ($config->isCronRunning()) {
	die("Cronjob already running.");
}
$config->markCronRunning();

$useFork = function_exists("pcntl_fork") && function_exists("pcntl_wait");
$childPids = array();
$maxChilds = 5;

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

	if ($useFork) {
		if ($maxChilds > 0 && count($childPids) >= $maxChilds) {
			$oldPid = pcntl_wait($status);
			unset($childPids[$oldPid]);
		}

		$pid = pcntl_fork();
		if ($pid > 0) {
			$childPids[$pid] = true;
			continue;
		}
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

	if ($useFork) {
		exit;
	}
}

while (!empty($childPids)) {
	$childPid = pcntl_wait($status);
	unset($childPids[$childPid]);
}

$config->markCronFinished();

?>
