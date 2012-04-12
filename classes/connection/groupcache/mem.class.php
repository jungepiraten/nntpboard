<?php

require_once(dirname(__FILE__) . "/../groupcache.class.php");

class MemGroupCacheConnection extends AbstractGroupCacheConnection {
	private $prefix;
	private $host;
	private $port;

	private $link;

	public function __construct($host, $port, $prefix, $uplink = null) {
		parent::__construct($uplink);
		$this->host = $host;
		$this->port = $port;
		$this->prefix = $prefix;
	}

	private function getLink() {
		if ($this->link === null) {
			$this->link = new Memcache;
			$this->link->pconnect($this->host, $this->port);
		}
		return $this->link;
	}
	
	/**
	 * Lade allgemeine Meta-Informationen
	 **/
	protected function loadGroup() {
		$link = $this->getLink();
		return $link->get($this->prefix);
	}

	/**
	 * Speichere alle Dateien wieder ab
	 **/
	protected function saveGroup($group) {
		$link = $this->getLink();
		$link->set($this->prefix, $group);
	}

	/**
	 * 
	 **/
	protected function getMessageQueue($queueid) {
		$link = $this->getLink();
		$queue = $link->get($this->prefix . "_messagequeue-" . $queueid);
		if ($queue != null) {
			return $queue;
		}
		return array();
	}

	protected function setMessageQueue($queueid, $queue) {
		$link = $this->getLink();
		$link->set($this->prefix . "_messagequeue-" . $queueid, $queue);
	}

	/**
	 * 
	 **/
	protected function loadGroupHash() {
		$link = $this->getLink();
		return $link->get($this->prefix . "_hash");
	}

	protected function saveGroupHash($hash) {
		$link = $this->getLink();
		$link->set($this->prefix . "_hash", $hash);
	}

	/**
	 * 
	 **/
	protected function loadLastThread() {
		$link = $this->getLink();
		return $link->get($this->prefix . "_lastthread");
	}

	protected function saveLastThread($lastthread) {
		$link = $this->getLink();
		$link->set($this->prefix . "_lastthread", $lastthread);
	}
}

?>
