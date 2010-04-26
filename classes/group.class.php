<?php

require_once(dirname(__FILE__)."/config.class.php");

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
			require_once(dirname(__FILE__)."/connection_cache.class.php");

			return new CacheConnection($this, $datadir);
		}
		return $this->getDirectConnection($this, $username, $password);
	}
	
	public function getDirectConnection($username = null, $password = null) {
		require_once(dirname(__FILE__)."/connection_nntp.class.php");

		return new NNTPConnection($this, $username, $password);
	}
}

?>
