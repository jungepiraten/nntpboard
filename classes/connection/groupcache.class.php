<?php

require_once(dirname(__FILE__) . "/cache.class.php");

abstract class AbstractGroupCacheConnection extends AbstractCacheConnection {
	private $group;
	// Meta-Infos ;)
	private $grouphash;
	private $lastthread;

	public function __construct($uplink = null) {
		parent::__construct($uplink);
	}
	
	abstract protected function loadGroup();
	abstract protected function saveGroup($group);
	abstract protected function loadGroupHash();
	abstract protected function saveGroupHash($hash);
	abstract protected function loadLastThread();
	abstract protected function saveLastThread($thread);
	
	public function open() {
		$this->grouphash = $this->loadGroupHash();
		$this->lastpostsubject = $this->loadLastThread();
	}

	public function close() {
		if ($this->group !== null) {
			$this->saveGroup($this->group);
			$this->saveGroupHash($this->group->getGroupHash());
			$this->saveLastThread($this->group->getLastThread());
		} else {
			$this->saveGroupHash($this->grouphash);
			$this->saveLastThread($this->lastthread);
		}
	}

	public function getGroup() {
		if ($this->group === null) {
			$this->group = $this->loadGroup();
		}
		if (!($this->group instanceof Group)) {
			$this->group = parent::getGroup();
		}
		return $this->group;
	}
	public function setGroup($group) {
		$this->group = $group;
		$this->setGroupHash($group->getGroupHash());
		$this->setLastThread($group->getLastThread());
	}

	public function getGroupHash() {
		return $this->grouphash;
	}
	public function setGroupHash($hash) {
		$this->grouphash = $hash;
	}

	public function getLastThread() {
		return $this->lastthread;
	}
	public function setLastThread($thread) {
		$this->lastthread = $thread;
	}
	public function updateGroup() {
		parent::updateGroup();
		$this->setLastThread($this->getGroup()->getLastThread());
	}
}

?>
