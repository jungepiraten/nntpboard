<?php

require_once(dirname(__FILE__) . "/cachednntp.class.php");
require_once(dirname(__FILE__) . "/../connection/itemcache/sql/mysql.class.php");

class SQLCachedNNTPBoard extends CachedNNTPBoard {
	public function __construct($boardid, $parentid, $name, $desc, $host, $group, $anonMayPost, $authMayPost, $isModerated) {
		parent::__construct($boardid, $parentid, $name, $desc, $host, $group, $anonMayPost, $authMayPost, $isModerated);
	}

	public function getConnection($auth) {
		return new MySQLItemCacheConnection(
		           "localhost", "root", "anything92", "nntpboard", "board" . $this->getBoardID() . "_",
		           parent::getConnection($auth)
		       );
	}
}

?>
