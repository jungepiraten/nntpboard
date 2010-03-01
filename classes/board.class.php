<?php

require_once(dirname(__FILE__)."/group.class.php");

class Board {
	private $boardid = 0;
	private $parent = null;
	private $name = "";
	private $desc = "";
	private $group;
	private $boards = array();
	private $subboards = array();

	public function __construct($boardid, $name, $desc, $group) {
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
	
	public function setDesc($desc) {
		$this->desc = $desc;
	}

	public function hasParent() {
		return ($this->parent !== null);
	}

	public function setParent($parent) {
		$this->parent = $parent;
		// Mache routen zu Untergeordneten Boards den uebergeordneten Boards bekannt
		foreach ($this->boards AS $boardid => $child) {
			$parent->registerBoard($this, $this->getBoard($boardid));
		}
	}

	public function getParent() {
		return $this->parent;
	}

	public function addSubBoard($board) {
		$board->setParent($this);
		$this->subboards[$board->getBoardID()] = $board;
		$this->registerBoard(null, $board);
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
	
	public function registerBoard($child, $board) {
		// Pfad fuer uns merken (bei null sind wir selbst das Vaterelement)
		$this->boards[$board->getBoardID()] = $child === null ? null : $child->getBoardID();
		// Pfad zum Board nach oben durchgeben
		if ($this->hasParent()) {
			$this->getParent()->registerBoard($this, $board);
		}
	}
	
	public function getBoards() {
		$boards = array($this);
		foreach ($this->getSubBoards() AS $subboard) {
			$boards = array_merge($boards, $subboard->getBoards());
		}
		return $boards;
	}
	
	public function getBoard($id) {
		// durchsuche die Untergeordneten Ebenen
		if (in_array($id, array_keys($this->boards))) {
			// es ist direkt Untergeordnet
			if ($this->boards[$id] === null) {
				return $this->getSubBoard($id);
			}
			// ansonsten laufen wir den Weg tiefer hinein
			return $this->getSubBoard($this->boards[$id])->getBoard($id);
		}
		// jetzt nach oben durchfragen
		if ($this->hasParent()) {
			return $this->getParent()->getBoard($id);
		}
		// und wenn wir da nichts finden, gibts das nicht!
		return null;
	}

	public function hasGroup() {
		return ($this->group !== null);
	}

	public function getGroup() {
		return $this->group;
	}
}

?>
