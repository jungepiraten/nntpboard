<?php

require_once(dirname(__FILE__) . "/../group.class.php");

class DynamicGroup extends AbstractGroup {
	private $messages = array();
	private $messagethreads;
	private $threads = array();
	private $threadslastpost;
	
	private $connection;
	
	public function __construct(AbstractItemCacheConnection $connection) {
		parent::__construct($connection->getGroupID());
		$this->connection = $connection;
		$this->messagethreads = $connection->loadMessageThreads();
		$this->sanitizeMessageThreads();
		$this->threadslastpost = $connection->loadThreadsLastPost();
		$this->sanitizeThreadsLastPost();
	}

	private function sanitizeMessageThreads() {
		if (!is_array($this->messagethreads)) {
			$this->messagethreads = array();
		}
	}

	private function sanitizeThreadsLastPost() {
		if (!is_array($this->threadslastpost)) {
			$this->threadslastpost = array();
		}
	}

	public function getGroupHash() {
		return $this->connection->getGroupHash();
	}
	public function setGroupHash($hash) {
		$this->connection->setGroupHash($hash);
	}

	/** Message **/
	public function getMessageIDs() {
		return array_keys($this->messagethreads);
	}
	public function getMessage($messageid) {
		if (!isset($this->messages[$messageid])) {
			$this->messages[$messageid] = $this->connection->loadMessage($messageid);
		}
		return $this->messages[$messageid];
	}

	/** Threads **/
	public function getThreadIDs() {
		return array_keys($this->threadslastpost);
	}
	public function hasThread($messageid) {
		return isset($this->messagethreads[$messageid]) and in_array($this->messagethreads[$messageid], $this->getThreadIDs());
	}
	public function getThread($threadid) {
		if (isset($this->messagethreads[$threadid])) {
			$threadid = $this->messagethreads[$threadid];
		}
		if (!isset($this->threads[$threadid])) {
			$this->threads[$threadid] = $this->connection->loadThread($threadid);
		}
		return $this->threads[$threadid];
	}

	public function hasLastThread() {
		return $this->getLastThread() !== null;
	}
	public function getLastThread() {
		return $this->connection->getLastThread();
	}

	/** Nachrichten **/
	public function addMessage($message) {
		parent::addMessage($message);
		$this->messages[$message->getMessageID()] = $message;
	}
	public function removeMessage($messageid) {
		parent::removeMessage($messageid);
		unset($this->messages[$messageid]);
		unset($this->messagethreads[$messageid]);
	}

	/** Threads **/
	public function addThread($thread) {
		$this->threads[$thread->getThreadID()] = $thread;
		$this->threadslastpost[$thread->getThreadID()] = $thread->getLastPostDate();
		asort($this->threadslastpost);
		if ($thread->getLastPostDate() > $this->getLastPostDate()) {
			$this->connection->setLastThread($thread);
		}
		foreach ($thread->getMessageIDs() as $messageid) {
			$this->messagethreads[$messageid] = $thread->getThreadID();
		}
	}
	public function removeThread($threadid) {
		foreach ($this->getThread($threadid)->getMessageIDs() as $messageid) {
			unset($this->messagethreads[$messageid]);
		}
		parent::removeThread($threadid);
		unset($this->threads[$threadid]);
		unset($this->threadslastpost[$threadid]);
	}

	/**
	 * Gebe die IDs durch, die sich geaendert haben (effizientes Speichern)
	 **/
	public function getMessageThreads() {
		return $this->messagethreads;
	}
	public function getThreadsLastPost() {
		return $this->threadslastpost;
	}
	public function getNewMessagesIDs() {
		return array_keys($this->messages);
	}
	public function getNewThreadIDs() {
		return array_keys($this->threads);
	}
}

?>
