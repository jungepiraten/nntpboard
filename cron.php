<?php

require_once(dirname(__FILE__)."/config.inc.php");

/**
 * Erstelle Caches
 **/
foreach ($config->getGroups() as $group) {
	$connection = $group->getConnection();
	try {
		$connection->open();
		$connection->loadMessages();
		$connection->close();
	} catch (Exception $e) {
		echo "<pre>".$e->getMessage()."</pre>";
	}
	$group->save();
}

?>
