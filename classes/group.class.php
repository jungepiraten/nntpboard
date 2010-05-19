<?php

class Group {
	private $mayread = true;
	private $maypost = false;
	private $ismoderated = false;

	// MessageID => Message
	private $messages = array();
	// ThreadID => Thread
	private $threads = array();

	public function __construct($mayread, $maypost, $ismoderated) {
		$this->mayread = $mayread;
		$this->maypost = $maypost;
		$this->ismoderated = $ismoderated;
	}
	
	public function mayRead() {
		return $this->mayread;
	}

	public function mayPost() {
		return $this->maypost;
	}

	public function isModerated() {
		return $this->ismoderated;
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
		return isset($this->threads[$threadid]);
	}
	public function getThread($threadid) {
		return $this->threads[$threadid];
	}

	private function getLastThread() {
		return $this->lastthread;
	}
	
	public function getLastPostMessageID() {
		return $this->getLastThread()->getLastPostMessageID();
	}

	public function getLastPostSubject($charset = null) {
		return $this->getLastThread()->getSubject($charset);
	}

	public function getLastPostDate() {
		return $this->getLastThread()->getLastPostDate();
	}

	public function getLastPostAuthor($charset = null) {
		return $this->getLastThread()->getLastPostAuthor($charset);
	}

	public function getLastPostThreadID() {
		return $this->getLastThread()->getThreadID();
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
		$thread->addMessage($message);

		$this->threads[$thread->getThreadID()] = $thread;
	}
}

?>
