<?php

require_once(dirname(__FILE__)."/exceptions/board.exception.php");

abstract class DefaultConfig {
	private $boards;
	
	public function getCharset() {
		return "UTF8";
	}

	protected function addBoard($parentid, $board) {
		if (isset($this->boards[$parentid])) {
			$this->boards[$parentid]->addSubBoard($board);
		}
		$this->boards[$board->getBoardID()] = $board;
	}
	public function getBoard($id = null) {
		if (isset($this->boards[$id])) {
			return $this->boards[$id];
		}
		throw new NotFoundBoardException($id);
	}
	protected function getBoardIDs() {
		return array_keys($this->boards);
	}
	
	abstract public function getAuth($user, $pass);
	abstract public function getAnonymousAuth();

	abstract public function getDatadir();
	abstract public function getTemplate($auth);

	abstract public function getMessageIDHost();

	public function getGroups() {
		$groups = array();
		foreach ($this->getBoardIDs() AS $boardid) {
			$board = $this->getBoard($boardid);
			if ($board->hasGroup()) {
				$groups[] = $board->getGroup();
			}
		}
		return $groups;
	}
}

?>
