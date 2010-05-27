<?php

interface Group {
	public function getGroupID();
	public function getGroupHash();

	public function getMessageIDs();
	public function getMessageCount();
	public function hasMessage($msgid);
	public function getMessage($msgid);

	public function getThreadIDs();
	public function getThreadCount();
	public function hasThread($msgid);
	public function getThread($msgid);

	public function getLastPostMessageID();
	public function getLastPostThreadID();
	public function getLastPostSubject($charset = null);
	public function getLastPostDate();
	public function getLastPostAuthor();

	public function addMessage($message);
	public function removeMessage($message);
}

abstract class AbstractGroup implements Group {
	private $groupid;
	private $grouphash;

	public function __construct($groupid, $grouphash) {
		$this->groupid = $groupid;
		$this->grouphash = $grouphash;
	}

	public function getGroupID() {
		return $this->groupid;
	}

	public function getGroupHash() {
		return $this->grouphash;
	}
	public function setGroupHash($hash) {
		$this->grouphash = $hash;
	}

	public function getMessageCount() {
		return count($this->getMessageIDs());
	}
	public function hasMessage($messageid) {
		return in_array($messageid, $this->getMessageIDs());
	}
	
	public function getThreadCount() {
		return count($this->getThreadIDs());
	}

	/** Last Thread **/
	abstract public function hasLastThread();
	abstract public function getLastThread();

	public function getLastPostThreadID() {
		if ($this->hasLastThread()) {
			return $this->getLastThread()->getThreadID();
		}
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

	/** Nachrichten hinzufuegen / verlinken **/
	public function addMessage($message) {
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
		$this->addThread($thread);

		return $thread;
	}
	abstract protected function addThread($thread);

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
			if ($thread->isEmpty()) {
				$this->removeThread($thread->getThreadID());
			}
		}
	}
	abstract protected function removeThread($threadid);
}

?>
