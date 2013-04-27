<?php

require_once(dirname(__FILE__) . "/cachednntp.class.php");
require_once(dirname(__FILE__) . "/../connection/itemcache/mixed.class.php");
require_once(dirname(__FILE__) . "/../connection/itemcache/file.class.php");
require_once(dirname(__FILE__) . "/../connection/itemcache/redis.class.php");

class RedisIndexedFileCachedNNTPBoard extends CachedNNTPBoard {
	private $rediscache;

	public function __construct($boardid, $parentid, $name, $desc, $indexer, $readAuthManager, $writeAuthManager, $isModerated, $rediscache, $host, $group) {
		parent::__construct($boardid, $parentid, $name, $desc, $indexer, $readAuthManager, $writeAuthManager, $isModerated, $host, $group);
		$this->rediscache = $rediscache;
	}

	public function getConnection() {
		return new MixedItemCacheConnection(array(
		           "index" => new RedisItemCacheConnection(
		               $this->rediscache,
		               parent::getConnection()
		           ),
		           "default" => new FileItemCacheConnection(
		               dirname(__FILE__) . "/../../cache/".$this->getBoardID()."/",
		               parent::getConnection()
		           )
		       ));
	}
}

?>
