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

	abstract public function loadMessageIDs();
	abstract protected function saveMessageIDs($messageids);
	abstract public function loadMessageThreads();
	abstract protected function saveMessageThreads($messagethreads);
	abstract public function loadThreadsLastPost();
	abstract protected function saveThreadsLastPost($messageids);
	abstract public function loadMessage($messageid);
	abstract protected function saveMessage($messageid, $message);
	abstract public function removeMessage($messageid);
	abstract public function loadThread($threadid);
	abstract protected function saveThread($threadid, $thread);
	abstract public function removeThread($threadid);
	abstract public function loadAcknowledges($messageid);
	abstract protected function loadGroupHash();
	abstract protected function saveGroupHash($hash);
	abstract protected function loadLastThread();
	abstract protected function saveLastThread($thread);
	
	public function open() {
		$this->grouphash = $this->loadGroupHash();
		$this->lastthread = $this->loadLastThread();
		// Fallback, falls wir ungueltige Daten bekommen
		if (!($this->lastthread instanceof Thread)) {
			$this->lastthread = null;
		}
	}
	
	public function close() {
		$this->saveGroupHash($this->grouphash);
		$this->saveLastThread($this->lastthread);
		if ($this->group !== null) {
			$this->saveMessageIDs($this->group->getMessageIDs());
			$this->saveMessageThreads($this->group->getMessageThreads());
			$this->saveThreadsLastPost($this->group->getThreadsLastPost());
			foreach ($this->group->getNewMessagesIDs() as $messageid) {
				$this->saveMessage($messageid, $this->group->getMessage($messageid));
			}
			foreach ($this->group->getNewThreadIDs() as $threadid) {
				$this->saveThread($threadid, $this->group->getThread($threadid));
			}
			foreach ($this->group->getAcknowledgeIDs() as $messageid) {
				$this->saveAcknowledges($messageid, $this->group->getAcknowledgeMessageIDs($messageid));
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
