<?php

require_once(dirname(__FILE__)."/group.class.php");

class Board {
	private $boardid = 0;
	private $parent = null;
	private $name = "";
	private $desc = "";
	private $connection = null;
	private $subboards = array();

	public function __construct($boardid, $name, $desc, Connection $connection = null) {
		$this->boardid = $boardid;
		$this->name = $name;
		$this->desc = $desc;
		$this->connection = $connection;
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

	public function hasGroup() {
		return ($this->connection !== null);
	}

	public function getConnection() {
		return $this->connection;
	}
}

?>
