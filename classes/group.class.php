<?php

class Group {
	private $groupid;

	// MessageID => Message
	private $messages = array();
	// ThreadID => Thread
	private $threads = array();
	// MessageID => ThreadID
	private $threadids = array();
	private $lastthread;

	public function __construct($groupid) {
		$this->groupid = $groupid;
	}

	public function getGroupID() {
		return $this->groupid;
	}

	public function getMessageIDs() {
		return array_keys($this->messages);
	}
	public function getMessageCount() {
		return count($this->messages);
	}
	public function hasMessage($msgid) {
		return isset($this->messages[$msgid]);
	}
	public function getMessage($msgid) {
		return $this->messages[$msgid];
	}

	public function getThreadIDs() {
		return array_keys($this->threads);
	}
	public function getThreadCount() {
		return count($this->threads);
	}
	public function hasThread($threadid) {
		return  isset($this->threads[$threadid])
		    or (isset($this->threadids[$threadid]) and $this->hasThread($this->threadids[$threadid]));
	}
	public function getThread($threadid) {
		if (isset($this->threads[$threadid])) {
			return $this->threads[$threadid];
		}
		if (isset($this->threadids[$threadid])) {
			return $this->getThread($this->threadids[$threadid]);
		}
	}

	private function getLastThread() {
		return $this->getThread($this->lastthread);
	}

	private function hasLastThread() {
		return $this->hasThread($this->lastthread);
	}
	
	public function getLastPostMessageID() {
		if ($this->hasLastThread()) {
			return $this->getLastThread()->getLastPostMessageID();
		}
	}

	public function getLastPostSubject($charset = null) {
		if ($this->hasLastThread()) {
			return $this->getLastThread()->getSubject($charset);
		}
	}

	public function getLastPostDate() {
		if ($this->hasLastThread()) {
			return $this->getLastThread()->getLastPostDate();
		}
	}

	public function getLastPostAuthor($charset = null) {
		if ($this->hasLastThread()) {
			return $this->getLastThread()->getLastPostAuthor($charset);
		}
	}

	public function getLastPostThreadID() {
		if ($this->hasLastThread()) {
			return $this->getLastThread()->getThreadID();
		}
	}

	public function addMessage($message) {
		$this->messages[$message->getMessageID()] = $message;

		// Unterpost verlinken
		if ($message->hasParent() && $this->hasMessage($message->getParentID())) {
			$this->getMessage($message->getParentID())->addChild($message);
		}
		
		// Zum Thread hinzufuegen
		if ($message->hasParent() && $this->hasThread($message->getParentID())) {
			$thread = $this->getThread($message->getParentID());
		} else {
			$thread = new Thread($message);
		}
		$this->threadids[$message->getMessageID()] = $thread->getThreadID();
		$thread->addMessage($message);
		
		$this->addThread($thread);
	}
	private function addThread($thread) {
		$this->threads[$thread->getThreadID()] = $thread;
		if (!isset($this->lastthread)
		 or $thread->getLastPostDate() > $this->getLastPostDate())
		{
			$this->lastthread = $thread->getThreadID();
		}
	}

	public function removeMessage($messageid) {
		$message = $this->getMessage($messageid);

		// Unterposts hochschieben
		$parent = null;
		if ($message->hasParent() && $this->hasMessage($message->getParentID())) {
			$parent = $this->getMessage($message->getParentID());
			$parent->removeChild($message);
		}
		foreach ($message->getChilds() as $childid) {
			if ($this->hasMessage($childid)) {
				$this->getMessage($childid)->setParent($parent);
			}
		}
		
		// Threading
		if ($this->hasThread($message->getMessageID())) {
			$thread = $this->getThread($message->getMessageID());
			$thread->removeMessage($message);
		}
		
		unset($this->messages[$message->getMessageID()]);
		unset($this->threadids[$message->getMessageID()]);
	}
}

?>
