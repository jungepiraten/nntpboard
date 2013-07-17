<?php

require_once(dirname(__FILE__) . "/host.class.php");

class SMTPHost extends Host {
	public function __construct($host = null, $port = 25) {
		parent::__construct($host, $port);
	}
}

?>
