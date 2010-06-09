<?php

require_once(dirname(__FILE__) . "/cachednntp.class.php");
require_once(dirname(__FILE__) . "/../connection/itemcache/mem.class.php");

class MemCachedNNTPBoard extends CachedNNTPBoard {
	public function __construct($boardid, $parentid, $name, $desc, $host, $group, $anonMayPost, $authMayPost, $isModerated) {
		parent::__construct($boardid, $parentid, $name, $desc, $host, $group, $anonMayPost, $authMayPost, $isModerated);
	}

	public function getConnection($auth) {
		return new MemItemCacheConnection(
		           "localhost", 11211, "nntpboard-" . $this->getBoardID(),
		           parent::getConnection($auth)
		       );
	}
}

?>
