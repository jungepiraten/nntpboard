<?php

require_once(dirname(__FILE__) . "/cache.class.php");

abstract class AbstractGroupCacheConnection extends AbstractCacheConnection {
	private $group;

	public function __construct($uplink = null) {
		parent::__construct($uplink);
	}
	
	abstract public function loadGroup();
	abstract public function saveGroup($group);
	
	public function open() {
		$this->group = $this->loadGroup();
	}

	public function close() {
		$this->saveGroup($this->group);
	}

	public function getGroup() {
		if (!($this->group instanceof Group)) {
			$this->group = parent::getGroup();
		}
		return $this->group;
	}

	public function setGroup($group) {
		$this->group = $group;
	}
}

?>
