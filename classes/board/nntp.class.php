<?php

require_once(dirname(__FILE__) . "/../board.class.php");
require_once(dirname(__FILE__) . "/../connection/messagestream/nntp.class.php");

class NNTPBoard extends Board {
	private $host;
	private $group;
	
	public function __construct($boardid, $parentid, $name, $desc, $anonMayPost, $authMayPost, $isModerated, $host, $group) {
		parent::__construct($boardid, $parentid, $name, $desc, $anonMayPost, $authMayPost, $isModerated);
		$this->host = $host;
		$this->group = $group;
	}
	
	public function hasThreads() {
		return true;
	}

	public function getConnection() {
		return new NNTPConnection($this->host, $this->group);
	}
}

?>
