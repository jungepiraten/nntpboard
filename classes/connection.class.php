<?php

interface Connection {
	public function open();
	public function close();
	public function getMessageCount();
	public function getMessageIDs();
	public function getGroup();
	public function post($message);
}

abstract class AbstractConnection implements Connection {
	public function __construct() {
	}

	abstract protected function mayRead();
	abstract protected function mayPost();
	abstract protected function isModerated();

	public function getGroup() {
		return new Group($this->mayRead(), $this->mayPost(), $this->isModerated());
	}
}

?>
