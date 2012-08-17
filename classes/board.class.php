<?php

require_once(dirname(__FILE__)."/group.class.php");

class Board {
	private $boardid = null;
	private $parentid = null;
	private $parent;
	private $name = "";
	private $desc = "";
	private $subboards = array();
	private $anonMayPost = false;
	private $authMayPost = true;
	private $isModerated = false;

	public function __construct($boardid, $parentid, $name, $desc, $anonMayPost = false, $authMayPost = true, $isModerated = false) {
		$this->boardid = $boardid;
		$this->parentid = $parentid;
		$this->name = $name;
		$this->desc = $desc;
		$this->anonMayPost = $anonMayPost;
		$this->authMayPost = $authMayPost;
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
		return true;
	}
	public function mayPost($auth) {
		if ($auth === null || $auth->isAnonymous()) {
			return $this->anonMayPost;
		}

		return $this->authMayPost;
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
