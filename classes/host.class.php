<?php

abstract class Host {
	private $host;
	private $port;

	public function __construct($host, $port) {
		$this->host = $host;
		$this->port = $port;
	}

	public function getHost() {
		return $this->host;
	}

	public function getPort() {
		return $this->port;
	}

	public function __toString() {
		return $this->getHost() . ":" . $this->getPort();
	}
}

?>
