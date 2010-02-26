<?php

require_once(dirname(__FILE__)."/config.inc.php");

/**
 * Erstelle Caches
 **/
foreach ($config->getGroups() as $group) {
	try {
		$group->init();
		$group->save();
	} catch (Exception $e) {
		echo $e->getMessage() . "\n";
	}
}

?>
