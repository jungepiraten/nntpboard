<?php

class MemCacheHost {
	private $host = "localhost";
	private $port = 11011;
	private $prefix = "";
	
	public function __construct($host = null, $port = null, $prefix = "") {
		if ($host !== null) {
			$this->host = $host;
		}
		if ($port !== null) {
			$this->port = $port;
		}
		$this->prefix = $prefix;
	}
	
	public function getHost() {
		return $this->host;
	}
	
	public function getPort() {
		return $this->port;
	}

	public function getKeyName($id) {
		return $this->prefix . $id;
	}

	public function __toString() {
		return $this->getHost() . ":" . $this->getPort();
	}
}

?>
