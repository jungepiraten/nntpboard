<?php

require_once(dirname(__FILE__) . "/cachedimap.class.php");
require_once(dirname(__FILE__) . "/../connection/cache/mem.class.php");

class MemCachedIMAPBoard extends CachedIMAPBoard {
	private $memcache;

	public function __construct($boardid, $parentid, $name, $desc, $indexer, $readAuthManager, $writeAuthManager, $memcache, $host, $loginusername, $loginpassword, $folder, $writer) {
		parent::__construct($boardid, $parentid, $name, $desc, $indexer, $readAuthManager, $writeAuthManager, $host, $loginusername, $loginpassword, $folder, $writer);
		$this->memcache = $memcache;
	}

	public function getConnection() {
		return new MemCacheConnection(
		           $this->memcache,
		           parent::getConnection()
		       );
	}
}

?>
