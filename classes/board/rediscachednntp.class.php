<?php

require_once(dirname(__FILE__) . "/cachednntp.class.php");
require_once(dirname(__FILE__) . "/../connection/cache/redis.class.php");

class RedisCachedNNTPBoard extends CachedNNTPBoard {
	private $rediscache;

	public function __construct($boardid, $parentid, $name, $desc, $indexer, $readAuthManager, $writeAuthManager, $isModerated, $rediscache, $host, $group) {
		parent::__construct($boardid, $parentid, $name, $desc, $indexer, $readAuthManager, $writeAuthManager, $isModerated, $host, $group);
		$this->rediscache = $rediscache;
	}

	public function getConnection() {
		return new RedisCacheConnection(
		           $this->rediscache,
		           parent::getConnection()
		       );
	}
}

?>
