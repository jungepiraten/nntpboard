<?php

interface Connection {
	public function open();
	public function close();

	// Meist sehr Kostenintensiv, da alle Posts geladen werden müssen
	public function getGroup();

	// Zum Schnellen Cache-Abgleich
	public function getGroupHash();
	public function getLastPostSubject($charset = null);
	public function getLastPostThreadID();
	public function getLastPostMessageID();
	public function getLastPostDate();
	public function getLastPostAuthor();

	public function postMessage($message);
	public function postAcknowledge($ack, $message);
	public function postCancel($cancel, $message);
}

abstract class AbstractConnection implements Connection {
	public function __construct() {
	}

	abstract protected function getGroupID();

	public function getLastPostSubject($charset = null) {
		return $this->getGroup()->getLastPostSubject($charset);
	}

	public function getLastPostThreadID() {
		return $this->getGroup()->getLastPostThreadID();
	}

	public function getLastPostMessageID() {
		return $this->getGroup()->getLastPostMessageID();
	}

	public function getLastPostDate() {
		return $this->getGroup()->getLastPostDate();
	}

	public function getLastPostAuthor() {
		return $this->getGroup()->getLastPostAuthor();
	}
}

?>
