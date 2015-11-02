<?php

require_once(dirname(__FILE__) . "/cachednntp.class.php");
require_once(dirname(__FILE__) . "/../connection/cache/mem.class.php");

class MemCachedNNTPBoard extends CachedNNTPBoard {
	private $memcache;

	public function __construct($boardid, $parentid, $name, $desc, $indexer, $readAuthManager, $writeAuthManager, $memcache, $host, $group) {
		parent::__construct($boardid, $parentid, $name, $desc, $indexer, $readAuthManager, $writeAuthManager, $host, $group);
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
