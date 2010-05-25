<?php

require_once(dirname(__FILE__) . "/nntp.class.php");
require_once(dirname(__FILE__) . "/../connection/filecache.class.php");
require_once(dirname(__FILE__) . "/../connection/memcache.class.php");

class CachedNNTPBoard extends NNTPBoard {
	public function __construct($boardid, $parentid, $name, $desc, $host, $group) {
		parent::__construct($boardid, $parentid, $name, $desc, $host, $group);
	}

	public function getConnection($auth) {
		// Memcache oder Filecache? ;)
		/**
		return new MemCacheConnection(
		           "localhost", 11211, "nntpboard-" . $this->getBoardID(),
		           parent::getConnection($auth)
		       );
		**/
		return new FileCacheConnection(
		           dirname(__FILE__) . "/../../cache/".$this->getBoardID()."/",
		           parent::getConnection($auth)
		       );
	}
}

?>
