#!/usr/bin/env php
<?php

require_once(dirname(__FILE__)."/../config.inc.php");

$indexer = $config->getIndexer();

foreach ($config->getBoardIDs() as $boardid) {
	$cache = $config->getBoard($boardid)->getConnection();

	if ($cache instanceof Connection) {
		// Benutze keine Authentifikation
		$cache->open(NULL);

		$group = $cache->getGroup();
		foreach ($group->getMessageIDs() as $messageid) {
			$message = $group->getMessage($messageid);
			// FIlter out Acknowledges
			if ($message instanceof Message) {
				$indexer->addMessage($boardid, $message);
			}
		}

		$cache->close();
	}
}

?>
