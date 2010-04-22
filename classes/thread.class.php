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
		$this->threadid = $message->getThreadID();
		$this->subject = $message->getSubject();
		$this->date = $message->getDate();
		$this->author = $message->getSender();
		$this->group = $message->getGroup();
	}
	
	public function getMessages($group) {
		$messages = array();
		foreach ($this->messages AS $messageid) {
			$messages[$messageid] = $this->getMessage($group, $messageid);
		}
		return $messages;
	}
	
	public function getMessage($group, $messageid) {
		return $group->getMessage($messageid);
	}
	
	public function addMessage($message) {
		if (!in_array($message->getMessageID(), $this->messages)) {
			if ($message->getDate() > $this->lastpostdate) {
				$this->lastpostmessageid = $message->getMessageID();
				$this->lastpostdate = $message->getDate();
				$this->lastpostauthor = $message->getSender();
			}
			$this->messages[] = $message->getMessageID();
		}
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
	
	public function getGroup() {
		return $this->group;
	}
}

?>
