<?php

abstract class DefaultConfig {
	public function getCharset() {
		return "UTF8";
	}

	abstract public function getAuth($user, $pass);
	abstract public function getAnonymousAuth();
	abstract public function getBoard($id = null);
	abstract public function getBoards();
	abstract public function getDatadir();
	abstract public function getTemplate($auth);
	abstract public function getMessageIDHost();

	public function getGroups() {
		$groups = array();
		foreach ($this->getBoards() AS $board) {
			if ($board->hasGroup()) {
				$groups[] = $board->getGroup();
			}
		}
		return $groups;
	}
}

?>
