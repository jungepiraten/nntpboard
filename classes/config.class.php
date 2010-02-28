<?php

require_once(dirname(__FILE__)."/host.class.php");
require_once(dirname(__FILE__)."/board.class.php");

class Config {
	private $boards = array();
	private $rootboard = null;
	
	public function __construct() {
		$this->rootboard = new Board(null, "Defaultname", "Defaultdesc", null, null);
	}
	
	public function setName($name) {
		$this->rootboard->setName($name);
	}
	
	public function setDesc($desc) {
		$this->rootboard->setDesc($desc);
	}
	
	public function addBoard(Board $board) {
		$this->boards[$board->getBoardID()] = $board;
		if (!$board->hasParent()) {
			$this->rootboard->addSubBoard($board);
			$board->setParent($this->rootboard);
		}
	}

	public function getBoard($id) {
		if ($id === null) {
			return $this->rootboard;
		}
		return $this->boards[$id];
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
