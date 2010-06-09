<?php

require_once(dirname(__FILE__) . "/cache.class.php");
require_once(dirname(__FILE__) . "/../group/dynamic.class.php");

abstract class AbstractItemCacheConnection extends AbstractCacheConnection {
	private $group;
	private $grouphash;
	private $lastthread;

	public function __construct($uplink = null) {
		parent::__construct($uplink);
	}

	abstract public function loadMessageThreads();
	abstract protected function saveMessageThreads($messagethreads);
	abstract public function loadThreadsLastPost();
	abstract protected function saveThreadsLastPost($messageids);
	abstract public function loadMessage($messageid);
	abstract protected function saveMessage($messageid, $message);
	abstract public function loadThread($threadid);
	abstract protected function saveThread($threadid, $thread);
	abstract protected function loadGroupHash();
	abstract protected function saveGroupHash($hash);
	abstract protected function loadLastThread();
	abstract protected function saveLastThread($thread);
	
	public function open() {
		$this->grouphash = $this->loadGroupHash();
		$this->lastthread = $this->loadLastThread();
		if (!($this->lastthread instanceof Thread)) {
			$this->lastthread = null;
		}
	}
	
	public function close() {
		$this->saveGroupHash($this->grouphash);
		$this->saveLastThread($this->lastthread);
		if ($this->group !== null) {
			$this->saveMessageThreads($this->group->getMessageThreads());
			$this->saveThreadsLastPost($this->group->getThreadsLastPost());
			foreach ($this->group->getNewMessagesIDs() AS $messageid) {
				$this->saveMessage($messageid, $this->group->getMessage($messageid));
			}
			foreach ($this->group->getNewThreadIDs() AS $threadid) {
				$this->saveThread($threadid, $this->group->getThread($threadid));
			}
		}
	}

	public function getGroup() {
		if ($this->group === null) {
			$this->group = new DynamicGroup($this);
		}
		return $this->group;
	}
	public function setGroup($group) {
		parent::setGroup($group);
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
	public function setLastThread($lastthread) {
		$this->lastthread = $lastthread;
	}
	public function updateGroup() {
		parent::updateGroup();
		$this->setLastThread($this->getGroup()->getLastThread());
	}
}

?>
