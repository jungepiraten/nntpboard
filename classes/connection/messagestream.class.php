<?php

require_once(dirname(__FILE__)."/../connection.class.php");

interface MessageStream {
	public function getMessageIDs();
	public function getMessageCount();
	public function hasMessage($messageid);
	public function getMessage($messageid);
}

abstract class AbstractMessageStreamConnection extends AbstractConnection implements MessageStream {
	private $group;

	public function __construct() {
		parent::__construct();
	}

	public function getGroup() {
		if ($this->group === null) {
			$this->group = new StaticGroup($this->getGroupID(), $this->getGroupHash());
		}
		return $this->group;
	}

	public function getGroupHash() {
		// Sonst muss fuer einen einfachen Abgleich die ganze Gruppe runtergeladen werden
		return md5($this->getGroupID() . "*" . $this->getMessageCount());
	}
}

?>
