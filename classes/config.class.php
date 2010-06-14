<?php

require_once(dirname(__FILE__)."/exceptions/board.exception.php");

abstract class DefaultConfig {
	private $boards;
	
	public function __construct() {}
	
	public function getCharset() {
		return "UTF8";
	}

	public function getThreadsPerPage() {
		return 20;
	}
	public function getMessagesPerPage() {
		return 15;
	}

	protected function addBoard($board) {
		if ($this->hasBoard($board->getParentID())) {
			$parent = $this->getBoard($board->getParentID());
			$board->setParent($parent);
			$parent->addSubBoard($board);
		}
		$this->boards[$board->getBoardID()] = $board;
	}
	public function hasBoard($id = null) {
		return isset($this->boards[$id]);
	}
	public function getBoard($id = null) {
		if (isset($this->boards[$id])) {
			return $this->boards[$id];
		}
		throw new NotFoundBoardException($id);
	}
	public function getBoardIDs() {
		return array_keys($this->boards);
	}

	public function getAddressLink($address) {
		return "mailto:" . $address->getAddress();
	}
	
	abstract public function getAuth($user, $pass);
	abstract public function getAnonymousAuth();

	abstract public function getTemplate($auth);

	abstract public function getMessageIDHost();
}

?>
