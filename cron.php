<?php

require_once(dirname(__FILE__)."/config.inc.php");

/**
 * Erstelle Caches
 **/
foreach ($config->getGroups() as $group) {
	$connection = $group->getConnection($config->getDataDir());
	$directconnection = $group->getDirectConnection();
	try {
		$directconnection->open();
		$connection->open();
		$connection->loadMessages($directconnection);
		$connection->close();
		$directconnection->close();
	} catch (Exception $e) {
		echo "<pre>".$e->getMessage()."</pre>";
	}
}

?>
