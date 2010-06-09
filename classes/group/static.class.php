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
	private $threadslastpost;

	public function __construct($groupid, $grouphash) {
		parent::__construct($groupid);
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
		if (isset($this->threads[$threadid])) {
			return $this->threads[$threadid];
		}
		if (isset($this->messagethreads[$threadid])) {
			return $this->getThread($this->messagethreads[$threadid]);
		}
	}

	/** Last Thread **/
	public function hasLastThread() {
		return $this->hasThread($this->getLastThread());
	}
	public function getLastThread() {
		return $this->getThread(array_pop(array_keys($this->threadslastpost)));
	}

	/** Hinzufuegen **/
	public function addMessage($message) {
		$thread = parent::addMessage($message);
		
		$this->messages[$message->getMessageID()] = $message;
		$this->messagethreads[$message->getMessageID()] = $thread->getThreadID();
	}
	protected function addThread($thread) {
		$this->threads[$thread->getThreadID()] = $thread;
		$this->threadslastpost[$thread->getThreadID()] = $thread->getLastPostDate();
		asort($this->threadslastpost);
	}

	/** Entfernen **/
	public function removeMessage($messageid) {
		parent::removeMessage($messageid);
		unset($this->messages[$messageid]);
		unset($this->messagethreads[$messageid]);
	}
	protected function removeThread($threadid) {
		unset($this->threads[$threadid]);
		unset($this->threadslastpost[$threadid]);
	}
}

?>
