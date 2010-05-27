<?php

require_once(dirname(__FILE__) . "/../group.class.php");

class StaticGroup extends AbstractGroup {
	// MessageID => Message
	private $messages = array();
	// ThreadID => Thread
	private $threads = array();
	// MessageID => ThreadID
	private $threadids = array();
	private $lastthread;

	public function __construct($groupid, $grouphash) {
		parent::__construct($groupid, $grouphash);
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
		return  isset($this->threadids[$threadid]) and $this->hasThread($this->threadids[$threadid]);
	}
	public function getThread($threadid) {
		if (isset($this->threads[$threadid])) {
			return $this->threads[$threadid];
		}
		if (isset($this->threadids[$threadid])) {
			return $this->getThread($this->threadids[$threadid]);
		}
	}

	/** Last Thread **/
	public function hasLastThread() {
		return $this->hasThread($this->lastthread);
	}
	public function getLastThread() {
		return $this->getThread($this->lastthread);
	}

	/** Hinzufuegen **/
	public function addMessage($message) {
		$thread = parent::addMessage($message);
		
		$this->messages[$message->getMessageID()] = $message;
		$this->threadids[$message->getMessageID()] = $thread->getThreadID();
	}
	protected function addThread($thread) {
		$this->threads[$thread->getThreadID()] = $thread;
		if (!isset($this->lastthread)
		 or $thread->getLastPostDate() > $this->getLastPostDate())
		{
			$this->lastthread = $thread->getThreadID();
		}
	}

	/** Entfernen **/
	public function removeMessage($messageid) {
		parent::removeMessage($messageid);

		unset($this->messages[$messageid]);
		unset($this->threadids[$messageid]);
	}
	protected function removeThread($threadid) {
		unset($this->threads[$threadid]);
		// TODO ggf. neuen LastThread suchen
	}
}

?>
