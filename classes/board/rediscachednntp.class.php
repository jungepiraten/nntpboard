<?php

require_once(dirname(__FILE__) . "/cachednntp.class.php");
require_once(dirname(__FILE__) . "/../connection/itemcache/redis.class.php");

class RedisCachedNNTPBoard extends CachedNNTPBoard {
	private $rediscache;

	public function __construct($boardid, $parentid, $name, $desc, $readAuthManager, $writeAuthManager, $isModerated, $rediscache, $host, $group) {
		parent::__construct($boardid, $parentid, $name, $desc, $readAuthManager, $writeAuthManager, $isModerated, $host, $group);
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
