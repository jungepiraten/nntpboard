<?php

class Thread {
	private $threadid;
	private $subject;
	private $date;
	private $author;
	private $lastpostmessageid;
	private $lastpostdate;
	private $lastpostauthor;
	private $messages = array();
	
	private $group;
	
	public function __construct($message) {
		$this->threadid = $message->getMessageID();
		$this->subject = $message->getSubject();
		$this->date = $message->getDate();
		$this->author = $message->getSender();
		$this->group = $message->getGroup();
	}
	
	public function getMessage($msgid) {
		return $this->getGroup()->getMessage($msgid);
	}
	
	public function addMessage($message) {
		if ($message->getDate() > $this->lastpostdate) {
			$this->lastpostmessageid = $message->getMessageID();
			$this->lastpostdate = $message->getDate();
			$this->lastpostauthor = $message->getSender();
		}
		$this->messages[] = $message->getMessageID();
	}
	
	public function getThreadID() {
		return $this->threadid;
	}
	
	public function getSubject() {
		return $this->subject;
	}
	
	public function getDate() {
		return $this->date;
	}
	
	public function getAuthor() {
		return $this->author;
	}
	
	public function getPosts() {
		return count($this->messages);
	}
	
	public function getLastPostMessageID() {
		return $this->lastpostmessageid;
	}
	
	public function getLastPostDate() {
		return $this->lastpostdate;
	}
	
	public function getLastPostAuthor() {
		return $this->lastpostauthor;
	}
	
	public function setGroup($group) {
		$this->group = $group;
	}
	
	public function getGroup() {
		return $this->group;
	}
}

?>
