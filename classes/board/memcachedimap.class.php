<?php

require_once(dirname(__FILE__) . "/cachedimap.class.php");
require_once(dirname(__FILE__) . "/../connection/itemcache/mem.class.php");

class MemCachedIMAPBoard extends CachedIMAPBoard {
	private $memcache;

	public function __construct($boardid, $parentid, $name, $desc, $indexer, $readAuthManager, $writeAuthManager, $isModerated, $memcache, $host, $loginusername, $loginpassword, $folder) {
		parent::__construct($boardid, $parentid, $name, $desc, $indexer, $readAuthManager, $writeAuthManager, $isModerated, $host, $loginusername, $loginpassword, $folder);
		$this->memcache = $memcache;
	}

	public function getConnection() {
		return new MemItemCacheConnection(
		           $this->memcache,
		           parent::getConnection()
		       );
	}
}

?>
