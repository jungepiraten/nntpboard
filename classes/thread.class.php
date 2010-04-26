<?php

class Thread {
	private $threadid;
	private $charset;
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
		$this->charset = $message->getCharset();
	}
	
	public function getMessages($connection = null) {
		if ($connection === null) {
			return $this->messages;
		}

		$messages = array();
		foreach ($this->getMessages() AS $messageid) {
			$messages[$messageid] = $connection->getMessage($messageid);
		}
		return $messages;
	}
	
	public function addMessage($message) {
		if (!in_array($message->getMessageID(), $this->messages)) {
			if ($message->getDate() > $this->lastpostdate) {
				$this->lastpostmessageid = $message->getMessageID();
				$this->lastpostdate = $message->getDate();
				$this->lastpostauthor = $message->getSender($this->getCharset());
			}
			$this->messages[] = $message->getMessageID();
		}
	}
	
	public function getThreadID() {
		return $this->threadid;
	}
	
	public function getSubject($charset = null) {
		if ($charset !== null) {
			return iconv($this->getCharset(), $charset, $this->getSubject());
		}
		return $this->subject;
	}
	
	public function getDate() {
		return $this->date;
	}
	
	public function getAuthor($charset = null) {
		if ($charset !== null) {
			return iconv($this->getCharset(), $charset, $this->getAuthor());
		}
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
	
	public function getLastPostAuthor($charset = null) {
		if ($charset !== null) {
			return iconv($this->getCharset(), $charset, $this->getLastPostAuthor());
		}
		return $this->lastpostauthor;
	}
	
	public function getCharset() {
		return $this->charset;
	}
	
	public function getGroup() {
		return $this->group;
	}
}

?>
