<?php

require_once(dirname(__FILE__) . "/cachedimap.class.php");
require_once(dirname(__FILE__) . "/../connection/itemcache/mixed.class.php");
require_once(dirname(__FILE__) . "/../connection/itemcache/file.class.php");
require_once(dirname(__FILE__) . "/../connection/itemcache/redis.class.php");

class RedisIndexedFileCachedIMAPBoard extends CachedIMAPBoard {
	private $rediscache;

	public function __construct($boardid, $parentid, $name, $desc, $indexer, $readAuthManager, $writeAuthManager, $isModerated, $rediscache, $host, $loginusername, $loginpassword, $folder, $writer) {
		parent::__construct($boardid, $parentid, $name, $desc, $indexer, $readAuthManager, $writeAuthManager, $isModerated, $host, $loginusername, $loginpassword, $folder, $writer);
		$this->rediscache = $rediscache;
	}

	public function getConnection() {
		return new MixedItemCacheConnection(
		       parent::getConnection(),
		       array(
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
