<?php

require_once(dirname(__FILE__) . "/cachedimap.class.php");
require_once(dirname(__FILE__) . "/../connection/itemcache/file.class.php");

class FileCachedIMAPBoard extends CachedIMAPBoard {
	public function __construct($boardid, $parentid, $name, $desc, $anonMayPost, $authMayPost, $isModerated, $host, $loginusername, $loginpassword, $folder) {
		parent::__construct($boardid, $parentid, $name, $desc, $anonMayPost, $authMayPost, $isModerated, $host, $loginusername, $loginpassword, $folder);
	}

	public function getConnection() {
		return new FileItemCacheConnection(
		           dirname(__FILE__) . "/../../cache/".$this->getBoardID()."/",
		           parent::getConnection()
		       );
	}
}

?>
