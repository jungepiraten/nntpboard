<?php

require_once(dirname(__FILE__) . "/cachedimap.class.php");
require_once(dirname(__FILE__) . "/../connection/itemcache/redis.class.php");

class RedisCachedIMAPBoard extends CachedIMAPBoard {
	private $rediscache;

	public function __construct($boardid, $parentid, $name, $desc, $indexer, $readAuthManager, $writeAuthManager, $isModerated, $rediscache, $host, $loginusername, $loginpassword, $folder, $writer) {
		parent::__construct($boardid, $parentid, $name, $desc, $indexer, $readAuthManager, $writeAuthManager, $isModerated, $host, $loginusername, $loginpassword, $folder, $writer);
		$this->rediscache = $rediscache;
	}

	public function getConnection() {
		return new RedisItemCacheConnection(
		           $this->rediscache,
		           parent::getConnection()
		       );
	}
}

?>
