<?php

interface Auth {
	public function isAnonymous();

	public function getAddress();
	public function getNNTPUsername();
	public function getNNTPPassword();

	public function transferRead($auth);
	public function isUnreadThread($thread);
	public function markReadThread($thread);
	public function isUnreadGroup($group);
	public function markReadGroup($group);
}

abstract class AbstractAuth implements Auth {
	const ZEITFRIST = 10800;	// 3 Stunden
	private $readdate = null;
	private $readthreads = array();
	private $readgroups = array();

	public function __construct() {
		$this->readdate = $this->loadReadDate();
		$this->readthreads = $this->loadReadThreads();
		$this->readgroups = $this->loadReadGroups();
	}

	public function getReadDate() {
		return $this->readdate;
	}

	public function getReadThreads() {
		return $this->readthreads;
	}

	public function getReadGroups() {
		return $this->readgroups;
	}

	protected function loadReadDate() {
		return time() - self::ZEITFRIST;
	}

	protected function loadReadThreads() {
		return array();
	}

	protected function loadReadGroups() {
		return array();
	}

	protected function saveReadDate($date) {}

	protected function saveReadThreads($data) {}

	protected function saveReadGroups($data) {}

	public function transferRead($auth) {
		if ($auth instanceof AbstractAuth) {
			$this->readdate = $auth->getReadDate();
			$this->readthreads = $auth->getReadThreads();
			$this->readgroups = $auth->getReadGroups();
		}
	}

	public function isUnreadThread($thread) {
		// Falls die Nachricht aelter als readdate ist, gilt sie als gelesen
		if ($thread->getLastPostDate() < $this->readdate) {
			return false;
		}
		// Entweder wir kennen den Thread noch gar nicht ...
		if (!isset($this->readthreads[$thread->getThreadID()])) {
			return true;
		}
		// ... oder der Timestamp hat sich veraendert
		if ($this->readthreads[$thread->getThreadID()] < $thread->getLastPostDate()) {
			return true;
		}
		return false;
	}

	public function markReadThread($thread) {
		// Trage den aktuellen Timestamp ein
		$this->readthreads[$thread->getThreadID()] = $thread->getLastPostDate();
		
		$this->saveReadThreads($this->readthreads);
	}

	public function generateUnreadArray($group) {
		$unreadthreads = array();
		foreach ($group->getThreadIDs() as $threadid) {
			if ($this->isUnreadThread($group->getThread($threadid))) {
				$unreadthreads[$threadid] = true;
			}
		}
		$this->readgroups[$group->getGroupID()][$group->getGroupHash()] = $unreadthreads;
		$this->saveReadGroups($this->readgroups);
	}

	public function isUnreadGroup($group) {
		if (!isset($this->readgroups[$group->getGroupID()][$group->getGroupHash()])) {
			$this->generateUnreadArray($group);
		}
		// Cache alle Thread-IDs, die in der Vergangenheit ungelesen waren
		foreach (array_keys($this->readgroups[$group->getGroupID()][$group->getGroupHash()]) as $threadid) {
			if ($this->isUnreadThread($group->getThread($threadid))) {
				return true;
			} else {
				unset($this->readgroups[$group->getGroupID()][$group->getGroupHash()][$threadid]);
				$this->saveReadGroups($this->readgroups);
			}
		}
		return false;
	}

	public function markReadGroup($group) {
		foreach ($group->getThreadIDs() as $threadid) {
			$this->markReadThread($group->getThread($threadid));
			unset($this->readgroups[$group->getGroupID()][$group->getGroupHash()][$threadid]);
		}
	}

	public function isAnonymous() {
		return $this->getAddress() == null;
	}
}

?>
