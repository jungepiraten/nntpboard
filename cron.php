<?php

require_once(dirname(__FILE__)."/config.inc.php");

/**
 * Erstelle Caches
 **/
foreach ($config->getGroups() as $group) {
	// Lade eventuell vorhandene Informationen, um Zeit zu sparen
	try {
		$group->load();
	} catch (Exception $e) {
		// Klappt nicht? Auch nicht weiter schlimm!
	}
	
	try {
		$group->init();
		$group->save();
	} catch (Exception $e) {
		echo $e->getMessage() . "\n";
	}
}

?>
