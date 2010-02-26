<?php

class Host {
	private $host = "localhost";
	private $port = 119;
	
	public function __construct($host = null, $port = null) {
		if ($host !== null) {
			$this->host = $host;
		}
		if ($port !== null) {
			$this->port = $port;
		}
	}
	
	public function getHost() {
		return $this->host;
	}
	
	public function getPort() {
		return $this->port;
	}
	
	public function getGroupString($group) {
		return "{" . $this->getHost() . ":" . $this->getPort() . "/nntp}" . $group;
	}
}

?>
