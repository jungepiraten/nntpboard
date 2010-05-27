<?php

require_once(dirname(__FILE__) . "/../group.class.php");

class DynamicGroup extends AbstractGroup {
	private $threadids;
	private $messagethreads;
	private $messages = array();
	private $threads = array();
	
	private $connection;
	
	public function __construct(AbstractItemCacheConnection $connection) {
		parent::__construct($connection->getGroupID(), $connection->getGroupHash());
		$this->connection = $connection;
		$this->messagethreads = $connection->loadMessageThreads();
	}

	public function getMessageThreads() {
		return $this->messagethreads;
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
		if ($this->threadids === null) {
			$this->threadids = array();
			$threadids = $this->connection->loadThreadIDs();
			foreach ($threadids as $threadid) {
				$this->threadids[$threadid] = true;
			}
		}
		return array_keys($this->threadids);
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
		return $this->connection->getLastThread() !== null;
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
		$this->threadids[$thread->getThreadID()] = true;
		if ($thread->getLastPostDate() > $this->getLastPostDate()) {
			$this->connection->setLastThread($thread);
		}
	}

	public function removeMessage($messageid) {
		parent::removeMessage($messageid);
		unset($this->messages[$messageid]);
		unset($this->messagethreads[$messageid]);
	}
	protected function removeThread($threadid) {
		unset($this->threads[$threadid]);
	}

	public function getNewMessagesIDs() {
		return array_keys($this->messages);
	}
	public function getNewThreadIDs() {
		return array_keys($this->threads);
	}
}

?>
