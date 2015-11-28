<?php

require_once(dirname(__FILE__) . "/host.class.php");

class IMAPHost extends Host {
	private $useStartTLS;

	public function __construct($host = null, $port = 143, $useStartTLS = true) {
		parent::__construct($host, $port);
		$this->useStartTLS = $useStartTLS;
	}

	public function useStartTLS() {
		return $this->useStartTLS;
	}
}

?>
