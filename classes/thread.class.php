<?php

class Thread {
	private $threadid;
	private $subject;
	private $date;
	private $author;
	private $lastpostdate;
	private $lastpostauthor;
	private $messages = array();
	
	public function __construct($message) {
		$this->threadid = $message->getMessageID();
		$this->subject = $message->getSubject();
		$this->date = $message->getDate();
		$this->author = $message->getSender();
	}
	
	public function getMessage($msgid) {
		// TODO - eigentlich sollten wir ueber die Group drann kommen :/
		//return $this->messages[$msgid];
	}
	
	public function addMessage($message) {
		if ($message->getDate() > $this->lastpostdate) {
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
	
	public function getLastPostDate() {
		return $this->lastpostdate;
	}
	
	public function getLastPostAuthor() {
		return $this->lastpostauthor;
	}
}

?>
