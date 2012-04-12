<?php

require_once(dirname(__FILE__) . "/cachednntp.class.php");
require_once(dirname(__FILE__) . "/../connection/itemcache/file.class.php");

class FileCachedNNTPBoard extends CachedNNTPBoard {
	public function __construct($boardid, $parentid, $name, $desc, $host, $group, $anonMayPost, $authMayPost, $isModerated) {
		parent::__construct($boardid, $parentid, $name, $desc, $host, $group, $anonMayPost, $authMayPost, $isModerated);
	}

	public function getConnection() {
		return new FileItemCacheConnection(
		           dirname(__FILE__) . "/../../cache/".$this->getBoardID()."/",
		           parent::getConnection()
		       );
	}
}

?>
