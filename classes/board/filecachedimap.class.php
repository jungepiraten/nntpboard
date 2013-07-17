<?php

require_once(dirname(__FILE__) . "/cachedimap.class.php");
require_once(dirname(__FILE__) . "/../connection/itemcache/file.class.php");

class FileCachedIMAPBoard extends CachedIMAPBoard {
	public function __construct($boardid, $parentid, $name, $desc, $indexer, $readAuthManager, $writeAuthManager, $isModerated, $host, $loginusername, $loginpassword, $folder, $writer) {
		parent::__construct($boardid, $parentid, $name, $desc, $indexer, $readAuthManager, $writeAuthManager, $isModerated, $host, $loginusername, $loginpassword, $folder, $writer);
	}

	public function getConnection() {
		return new FileItemCacheConnection(
		           dirname(__FILE__) . "/../../cache/".$this->getBoardID()."/",
		           parent::getConnection()
		       );
	}
}

?>
