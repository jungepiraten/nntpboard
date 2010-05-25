<?php

interface Connection {
	public function open();
	public function close();
	public function getGroup();
	public function getMessageCount();
	public function post($message);
}

abstract class AbstractConnection implements Connection {
	public function __construct() {
	}

	abstract protected function getGroupID();

	public function getGroup() {
		return new Group($this->getGroupID());
	}

	/** Messages / TODO depreaced - nur fuer Cache-Abgleich noch sinnvoll **/
	public function getMessageCount() {
		return $this->getGroup()->getMessageCount();
	}
}

?>
