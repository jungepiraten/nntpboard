<?php

require_once(dirname(__FILE__)."/group.class.php");

class Board {
	private $boardid = 0;
	private $parent = "";
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
		if ($parent === null) {
			$this->parent = $parent;
		}
	}

	public function getBoardID() {
		return $this->boardid;
	}

	public function hasParent() {
		return ($this->parent !== null);
	}

	public function getParent() {
		return $this->parent;
	}

	public function addSubBoard($board) {
		$this->subboards[] = $board;
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
