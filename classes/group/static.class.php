<?php

require_once(dirname(__FILE__) . "/../group.class.php");

class StaticGroup extends AbstractGroup {
	private $grouphash;

	// MessageID => Message
	private $messages = array();
	// MessageID => ThreadID
	private $messagethreads = array();
	// ThreadID => Thread
	private $threads = array();
	// ThreadID => LastPostDate
	private $threadslastpost = array();
	// MessageID => Acknowledge[]
	private $acknowledges = array();

	public function __construct($groupid, $boardindexer, $grouphash) {
		parent::__construct($groupid, $boardindexer);
		$this->grouphash = $grouphash;
	}

	public function getGroupHash() {
		return $this->grouphash;
	}
	public function setGroupHash($hash) {
		$this->grouphash = $hash;
	}

	/** Messages **/
	public function getMessageIDs() {
		return array_keys($this->messages);
	}
	public function getMessage($msgid) {
		if (!($this->messages[$msgid] instanceof Message) && !($this->messages[$msgid] instanceof Acknowledge)) {
			throw new Exception("Loading of Message " . $msgid . " failed: Returnvalue neither instanceof Message or Acknowledge");
		}
		return $this->messages[$msgid];
	}

	/** Threads **/
	public function getThreadIDs() {
		return array_keys($this->threads);
	}
	public function hasThread($threadid) {
		return  isset($this->messagethreads[$threadid]) and $this->hasThread($this->messagethreads[$threadid]);
	}
	public function getThread($threadid) {
		if (isset($this->messagethreads[$threadid])) {
			$threadid = $this->messagethreads[$threadid];
		}
		if (isset($this->threads[$threadid])) {
			if (!($this->threads[$threadid] instanceof Thread)) {
				throw new Exception("Loading of Thread " . $threadid . " failed: Returnvalue not instanceof Thread");
			}
			return $this->threads[$threadid];
		}
	}

	/** Last Thread **/
	public function hasLastThread() {
		return $this->getLastThreadID() != null && $this->getLastThread() != null;
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
	}

	/** Threads **/
	public function addThread($thread) {
		$this->threads[$thread->getThreadID()] = $thread;
		$this->threadslastpost[$thread->getThreadID()] = $thread->getLastPostDate();
		asort($this->threadslastpost);
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

	/** Acknowledges **/
	public function getAcknowledgeMessageIDs($messageid) {
		return array_keys($this->acknowledges[$messageid]);
	}
	protected function addAcknowledge($ack) {
		$this->acknowledges[$ack->getReference()][$ack->getMessageID()] = TRUE;
	}
	protected function removeAcknowledge($acknowledgeid, $referenceid) {
		unset($this->acknowledges[$referenceid][$acknowledgeid]);
	}
}

?>
