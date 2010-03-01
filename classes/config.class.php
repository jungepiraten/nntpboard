<?php

require_once(dirname(__FILE__)."/host.class.php");
require_once(dirname(__FILE__)."/board.class.php");

class Config {
	private $rootboard = null;
	
	public function __construct() {
		$this->rootboard = new Board(null, "Defaultname", "Defaultbeschreibung", null);
	}
	
	public function getBoard($id = null) {
		if ($id === null) {
			return $this->rootboard;
		}
		return $this->getBoard()->getBoard($id);
	}
	
	public function setBoard($board) {
		$this->rootboard = $board;
	}
	
	public function getBoards() {
		return $this->rootboard->getBoards();
	}
	
	public function getGroups() {
		$groups = array();
		foreach ($this->getBoards() AS $board) {
			if ($board->hasGroup()) {
				$groups[] = $board->getGroup();
			}
		}
		return $groups;
	}
}

?>
