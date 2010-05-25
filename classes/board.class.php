<?php

require_once(dirname(__FILE__)."/group.class.php");

class Board {
	private $boardid = null;
	private $parentid = null;
	private $parent;
	private $name = "";
	private $desc = "";
	private $subboards = array();

	public function __construct($boardid, $parentid, $name, $desc) {
		$this->boardid = $boardid;
		$this->parentid = $parentid;
		$this->name = $name;
		$this->desc = $desc;
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

	public function getBoardID() {
		return $this->boardid;
	}

	public function hasParent() {
		return ($this->parent !== null);
	}

	public function setParent(&$parent) {
		$this->parent = $parent;
	}

	public function getParent() {
		return $this->parent;
	}

	public function getParentID() {
		return $this->parentid;
	}

	public function addSubBoard($board) {
		$this->subboards[$board->getBoardID()] = $board;
	}
	
	public function hasSubBoards() {
		return !empty($this->subboards);
	}

	public function getSubBoardIDs() {
		return array_keys($this->subboards);
	}

	public function getSubBoard($id) {
		return $this->subboards[$id];
	}

	// TODO - dynamischer output und so
	public function mayRead($auth) {
		return true;
	}
	public function mayPost($auth) {
		return true;
	}
	public function isModerated() {
		return false;
	}

	public function hasThreads() {
		return false;
	}

	public function getConnection($auth) {
		return null;
	}
}

?>
