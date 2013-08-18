<?php

require_once(dirname(__FILE__) . "/../group.class.php");

class DynamicGroup extends AbstractGroup {
	private $messages = array();
	private $messagethreads = array();
	private $threads = array();
	private $threadslastpost = array();
	private $acknowledges = array();

	private $connection;

	public function __construct(AbstractItemCacheConnection $connection) {
		parent::__construct($connection->getGroupID(), $connection->getBoardIndexer());
		$this->connection = $connection;
		$messageids = $this->sanitizeMessageIDs($connection->loadMessageIDs());
		if (count($messageids) > 0) {
			$this->messages = array_combine($messageids,
			                                array_fill(0, count($messageids), null));
		} else {
			$this->messages = array();
		}
		$this->messagethreads = $this->sanitizeMessageThreads($connection->loadMessageThreads());
		$this->threadslastpost = $this->sanitizeThreadsLastPost($connection->loadThreadsLastPost());
	}

	private function sanitizeMessageThreads($threads) {
		if (!is_array($threads)) {
			return array();
		}
		return $threads;
	}

	private function sanitizeThreadsLastPost($lastposts) {
		if (!is_array($lastposts)) {
			return array();
		}
		return $lastposts;
	}

	private function sanitizeMessage($message) {
		return $message;
	}

	private function sanitizeThread($thread) {
		return $thread;
	}

	private function sanitizeMessageIDs($messageids) {
		if (!is_array($messageids)) {
			return array();
		}
		return $messageids;
	}

	public function getGroupHash() {
		return $this->connection->getGroupHash();
	}
	public function setGroupHash($hash) {
		$this->connection->setGroupHash($hash);
	}

	/** Message **/
	public function getMessageIDs() {
		return array_keys($this->messages);
	}
	public function getMessage($messageid) {
		if (!isset($this->messages[$messageid])) {
			$this->messages[$messageid] = $this->sanitizeMessage($this->connection->loadMessage($messageid));
			if ($this->messages[$messageid] == null) {
				unset($this->messages[$messageid]);
				unset($this->messagethreads[$messageid]);
				if ($this->hasThread($messageid)) {
					$this->getThread($messageid)->removeMessage($messageid);
			        }
			        throw new Exception("Error at Message " . $messageid . " - tried to fix it automatically");
			}
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
			$this->threads[$threadid] = $this->sanitizeThread($this->connection->loadThread($threadid));
			if ($this->threads[$threadid] == null) {
				unset($this->threads[$threadid]);
				unset($this->threadslastpost[$threadid]);
				throw new Exception("Error at Thread " . $threadid . " - tried to fix it automatically");
			}
		}
		return $this->threads[$threadid];
	}

	/** Last Thread **/
	public function hasLastThread() {
		return $this->getLastThreadID() != null && $this->hasThread($this->getLastThreadID());
	}
	private function getLastThreadID() {
		if (count($this->threadslastpost) == 0) {
			return null;
		}
		return array_pop(array_keys($this->threadslastpost));
	}
	public function getLastThread() {
		return $this->getThread($this->getLastThreadID());
	}

	/** Nachrichten **/
	public function addMessage($message) {
		parent::addMessage($message);
		$this->messages[$message->getMessageID()] = $message;
	}
	public function removeMessage($messageid) {
		parent::removeMessage($messageid);
		// LastPost neu arrangieren
		if ($this->hasThread($messageid)) {
			$thread = $this->getThread($messageid);
			$this->threadslastpost[$thread->getThreadID()] = $thread->getLastPostDate();
			asort($this->threadslastpost);
		}

		unset($this->messages[$messageid]);
		unset($this->messagethreads[$messageid]);
		$this->connection->removeMessage($messageid);
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
		$this->connection->removeThread($threadid);
		// Last post updaten
		$this->connection->setLastThread($this->getLastThread());
	}

	/** Acknowledges **/
	public function getAcknowledgeMessageIDs($messageid) {
		if (!isset($this->acknowledges[$messageid])) {
			$this->acknowledges[$messageid] = array_flip($this->sanitizeMessageIDs($this->connection->loadAcknowledges($messageid)));
		}
		return array_keys($this->acknowledges[$messageid]);
	}
	protected function addAcknowledge($ack) {
		if (!isset($this->acknowledges[$ack->getReference()])) {
			$this->acknowledges[$ack->getReference()] = array_flip($this->sanitizeMessageIDs($this->connection->loadAcknowledges($ack->getReference())));
		}
		$this->acknowledges[$ack->getReference()][$ack->getMessageID()] = TRUE;
	}
	protected function removeAcknowledge($messageid, $reference) {
		if (!isset($this->acknowledges[$reference])) {
			$this->acknowledges[$reference] = array_flip($this->sanitizeMessageIDs($this->connection->loadAcknowledges($reference)));
		}
		unset($this->acknowledges[$reference][$messageid]);
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
	public function getAcknowledgeIDs() {
		return array_keys($this->acknowledges);
	}
}

?>
