<?php

require_once(dirname(__FILE__)."/config.inc.php");

/**
 * Erstelle Caches
 **/
foreach ($config->getGroups() as $group) {
	$group->open($config->getDataDir());
	$connection = $group->getConnection();
	try {
		$connection->open();
		$connection->loadMessages($config->getCharset());
		$connection->close();
	} catch (Exception $e) {
		echo "<pre>".$e->getMessage()."</pre>";
	}
	//var_dump($group);
	$group->close();
}

?>
