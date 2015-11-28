<?php

require_once(dirname(__FILE__) . "/keyvalue.class.php");

class MemCacheConnection extends KeyValueCacheConnection {
	private $memcache;
	private $link;

	public function __construct($memcache, $uplink) {
		parent::__construct($uplink);
		$this->memcache = $memcache;
	}

	private function getLink() {
		if ($this->link === null) {
			$this->link = new Memcache;
			$this->link->pconnect($this->memcache->getHost(), $this->memcache->getPort());
		}
		return $this->link;
	}

	protected function get($key) {
		return $this->getLink()->get($this->memcache->getKeyName($key));
	}

	protected function set($key, $val) {
		$this->getLink()->set($this->memcache->getKeyName($key), $val);
	}

	protected function delete($key) {
		$this->getLink()->delete($this->memcache->getKeyName($key));
	}
}

?>
