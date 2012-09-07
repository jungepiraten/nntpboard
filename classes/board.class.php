<?php

require_once(dirname(__FILE__)."/group.class.php");

class Board {
	private $boardid = null;
	private $parentid = null;
	private $parent;
	private $name = "";
	private $desc = "";
	private $subboards = array();
	private $readAuthManager;
	private $writeAuthManager;
	private $isModerated = false;

	public function __construct($boardid, $parentid, $name, $desc, $readAuthManager = null, $writeAuthManager = null, $isModerated = false) {
		$this->boardid = $boardid;
		$this->parentid = $parentid;
		$this->name = $name;
		$this->desc = $desc;
		$this->readAuthManager = $readAuthManager;
		$this->writeAuthManager = $writeAuthManager;
		$this->isModerated = $isModerated;
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

	public function mayRead($auth) {
		return $this->readAuthManager->isAllowed($auth);
	}

	public function mayPost($auth) {
		return $this->writeAuthManager->isAllowed($auth);
	}

	public function mayAcknowledge($auth) {
		return $this->mayPost($auth);
	}

	public function isModerated() {
		return $this->isModerated;
	}

	// Per Default nutzen wir keine Verbindung - Unterklassen sollten eine Zurueckgeben
	public function hasThreads() {
		return false;
	}

	// in KBytes
	public function getMaxAttachmentSize() {
		return 512;
	}

	public function getConnection() {
		return null;
	}
}

?>
