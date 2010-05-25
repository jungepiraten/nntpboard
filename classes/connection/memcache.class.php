<?php

require_once(dirname(__FILE__) . "/groupcache.class.php");

class MemCacheConnection extends AbstractGroupCacheConnection {
	private $prefix;
	private $host;
	private $port;

	private $group;

	public function __construct($host, $port, $prefix, $uplink = null) {
		parent::__construct($uplink);
		$this->host = $host;
		$this->port = $port;
		$this->prefix = $prefix;
	}

	private function getLink() {
		$link = new Memcache;
		$link->pconnect($this->host, $this->port);
		return $link;
	}
	
	/**
	 * Lade algemeine Meta-Informationen
	 **/
	public function loadGroup() {
		$link = $this->getLink();
		$group = $link->get($this->prefix);
		return $group;
	}

	/**
	 * Speichere alle Dateien wieder ab
	 **/
	public function saveGroup($group) {
		$link = $this->getLink();
		$link->set($this->prefix, $group);
	}
}

?>
