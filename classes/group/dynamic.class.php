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
		if (!is_array($this->messagethreads)) {
			$this->messagethreads = array();
		}
		$this->threadslastpost = $connection->loadThreadsLastPost();
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
	public function getThread($messageid) {
		$threadid = $this->messagethreads[$messageid];
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

	public function addMessage($message) {
		$thread = parent::addMessage($message);
		$this->messages[$message->getMessageID()] = $message;
		$this->messagethreads[$message->getMessageID()] = $thread->getThreadID();
	}
	protected function addThread($thread) {
		$this->threads[$thread->getThreadID()] = $thread;
		$this->threadslastpost[$thread->getThreadID()] = $thread->getLastPostDate();
		if ($thread->getLastPostDate() > $this->getLastPostDate()) {
			$this->connection->setLastThread($thread);
		}
		asort($this->threadslastpost);
	}

	public function removeMessage($messageid) {
		parent::removeMessage($messageid);
		unset($this->messages[$messageid]);
		unset($this->messagethreads[$messageid]);
	}
	protected function removeThread($threadid) {
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
