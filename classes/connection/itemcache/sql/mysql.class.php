<?php

require_once(dirname(__FILE__) . "/../sql.class.php");

class MySqlItemCacheConnection extends AbstractSQLItemCacheConnection {
	public function __construct($host, $user, $pass, $db, $prefix, $uplink) {
		parent::__construct($prefix, $uplink);
	}

	public function query($sql) {
		var_dump($sql);
	}
}

?>
