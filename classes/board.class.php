<?php

require_once(dirname(__FILE__)."/group.class.php");

class Board {
	private $boardid = 0;
	private $parent = null;
	private $name = "";
	private $desc = "";
	private $group;
	private $subboards = array();

	public function __construct($boardid, $name, $desc, $group = null) {
		$this->boardid = $boardid;
		$this->name = $name;
		$this->desc = $desc;
		$this->group = $group;
	}

	public function getBoardID() {
		return $this->boardid;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function setName($name) {
		$this->name = $name;
	}
	
	public function getDesc() {
		return $this->desc;
	}

	public function hasParent() {
		return ($this->parent !== null);
	}

	public function setParent($parent) {
		$this->parent = $parent;
	}

	public function getParent() {
		return $this->parent;
	}

	public function addSubBoard($board) {
		$board->setParent($this);
		$this->subboards[$board->getBoardID()] = $board;
	}
	
	public function getSubBoard($id) {
		return $this->subboards[$id];
	}
	
	public function hasSubBoards() {
		return !empty($this->subboards);
	}

	public function getSubBoards() {
		return $this->subboards;
	}
	
	public function getBoards() {
		$boards = array($this);
		foreach ($this->getSubBoards() AS $subboard) {
			$boards = array_merge($boards, $subboard->getBoards());
		}
		return $boards;
	}

	public function hasGroup() {
		return ($this->group !== null);
	}

	public function getGroup() {
		return $this->group;
	}
}

?>
