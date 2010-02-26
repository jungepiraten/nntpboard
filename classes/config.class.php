<?php

require_once(dirname(__FILE__)."/host.class.php");
require_once(dirname(__FILE__)."/board.class.php");

class Config {
	private $host = null;
	private $username = "";
	private $password = "";
	private $boards = array();
	private $rootboards = array();
	
	public function getHost() {
		return $this->host;
	}
	
	public function setHost($host, $username = null, $password = null) {
		if ($host !== null) {
			$this->host = $host;
		} else {
			$this->host = new Host();
		}
		if ($username !== null) {
			$this->username = $username;
		}
		if ($host !== null) {
			$this->password = $password;
		}
	}

	public function getUsername() {
		return $this->username;
	}
	
	public function getPassword() {
		return $this->password;
	}
	
	public function addBoard(Board $board) {
		$this->boards[$board->getBoardID()] = $board;
		if (!$board->hasParent()) {
			$this->rootboards[$id] = $board;
		}
	}

	public function getBoard($id) {
		return $this->boards[$id];
	}

	public function getRootBoards() {
		return $this->rootboards;
	}
		
	public function getGroups() {
		$groups = array();
		foreach ($this->boards AS $board) {
			$groups[] = $board->getGroup();
		}
		return $groups;
	}
}

?>
