<?php

require_once(dirname(__FILE__) . "/host.class.php");

class IMAPHost extends Host {
	public function __construct($host = null, $port = 143) {
		parent::__construct($host, $port);
	}
}

?>
