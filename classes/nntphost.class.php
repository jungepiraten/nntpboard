<?php

require_once(dirname(__FILE__) . "/host.class.php");

class NNTPHost extends Host {
	public function __construct($host = null, $port = 119) {
		parent::__construct($host, $port);
	}
}

?>
