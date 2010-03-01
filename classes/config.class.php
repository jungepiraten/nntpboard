<?php

require_once(dirname(__FILE__)."/host.class.php");
require_once(dirname(__FILE__)."/board.class.php");

class Config {
	private $datadir = null;
	private $rootboard = null;
	
	public function __construct() {
		// Default-Werte
		$this->datadir = new Datadir(dirname(__PATH__)."/../groups", "./groups");
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
		return $this->getBoard()->getBoards();
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
	
	
	public function setDatadir($datadir) {
		$this->datadir = $datadir;
	}
	
	public function getDatadir() {
		return $this->datadir;
	}
}

?>
