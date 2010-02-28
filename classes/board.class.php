<?php

require_once(dirname(__FILE__)."/group.class.php");

class Board {
	private $boardid = 0;
	private $parent = null;
	private $name = "";
	private $desc = "";
	private $group;
	private $subboards = array();

	public function __construct($boardid, $name, $desc, $group, $parent = null) {
		global $config;

		if (is_string($group)) {
			$group = new Group($config->getHost(), $group, $config->getUsername(), $config->getPassword());
		}
		
		$this->boardid = $boardid;
		$this->name = $name;
		$this->desc = $desc;
		$this->group = $group;
		if ($parent !== null) {
			$this->parent = $parent;
			$this->parent->addSubBoard($this);
		}
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
	
	public function setDesc($desc) {
		$this->desc = $desc;
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
		$this->subboards[] = $board;
	}
	
	public function hasSubBoards() {
		return !empty($this->subboards);
	}

	public function getSubBoards() {
		return $this->subboards;
	}

	public function getGroup() {
		return $this->group;
	}

	public function getGroups() {
		$groups = array($this->getGroup());
		foreach ($this->getSubBoards() AS $board) {
			$groups = array_merge($groups, $board->getGroups());
		}
		return $groups;
	}
}

?>
