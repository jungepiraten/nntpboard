<?php

require_once(dirname(__FILE__) . "/cachednntp.class.php");
require_once(dirname(__FILE__) . "/../connection/itemcache/mem.class.php");

class MemCachedNNTPBoard extends CachedNNTPBoard {
	private $memcache;

	public function __construct($boardid, $parentid, $name, $desc, $anonMayPost, $authMayPost, $isModerated, $memcache, $host, $group) {
		parent::__construct($boardid, $parentid, $name, $desc, $anonMayPost, $authMayPost, $isModerated, $host, $group);
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
