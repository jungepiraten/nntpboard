<?php

require_once(dirname(__FILE__)."/config.class.php");
require_once(dirname(__FILE__)."/connection.class.php");

class Group {
	private $host;
	private $group;

	public function __construct(Host $host, $group) {
		$this->host = $host;
		$this->group = $group;
	}
	
	public function getHost() {
		return $this->host;
	}
	
	public function getGroup() {
		return $this->group;
	}
	
	public function getConnection($datadir = null, $username = null, $password = null) {
		if ($username === null && $password === null) {
			return new CacheConnection($this, $datadir);
		}
		return $this->getDirectConnection($this, $username, $password);
	}
	
	public function getDirectConnection($username = null, $password = null) {
		return new NNTPConnection($this, $username, $password);
	}
}

?>
