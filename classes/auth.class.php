<?php

interface Auth {
	public function isAnonymous();

	public function getAddress();
	public function getNNTPUsername();
	public function getNNTPPassword();

	// TODO grml - ungelesene threads
	public function isUnreadThread($thread);
	public function markReadThread($thread);
}

abstract class AbstractAuth implements Auth {
	public function isAnonymous() {
		return $this->getAddress() == null;
	}
}

?>
