<?php

require_once(dirname(__FILE__) . "/keyvalue.class.php");
require_once(dirname(__FILE__) . "/redis/RedisServer.php");

class RedisCacheConnection extends KeyValueCacheConnection {
	private $rediscache;
	private $link;

	public function __construct($rediscache, $uplink) {
		parent::__construct($uplink);
		$this->rediscache = $rediscache;
	}

	private function getLink() {
		if ($this->link === null) {
			$this->link = new RedisServer($this->rediscache->getHost(), $this->rediscache->getPort());
			$this->link->connect($this->rediscache->getHost(), $this->rediscache->getPort());
		}
		return $this->link;
	}

	protected function get($key) {
		return unserialize($this->getLink()->Get($this->rediscache->getKeyName($key)));
	}

	protected function set($key, $val) {
		$this->getLink()->Set($this->rediscache->getKeyName($key), serialize($val));
	}

	protected function delete($key) {
		$this->getLink()->Del($this->rediscache->getKeyName($key));
	}
}

?>
