<?php

class Thread {
	private $threadid;
	private $charset;
	private $subject;
	private $date;
	private $author;
	private $messages = array();
	
	public static function getByMessage($message) {
		return new Thread($message->getMessageID(), $message->getSubject(), $message->getDate(), $message->getAuthor(), $message->getCharset());
	}
	
	public function __construct($threadid, $subject, $date, $author, $charset) {
		$this->threadid = $threadid;
		$this->subject = $subject;
		$this->date = $date;
		$this->author = $author;
		$this->charset = $charset;
	}
	
	public function getMessageIDs() {
		return array_keys($this->messages);
	}

	public function getMessageCount() {
		return count($this->messages);
	}
	
	public function addMessage($message) {
		$this->messages[$message->getMessageID()] = array("date" => $message->getDate(), "author" => $message->getAuthor());
		$this->sort();
	}

	public function getMessagePosition($message) {
		if ($message instanceof Message) {
			$message = $message->getMessageID();
		}
		$positions = array_flip(array_keys($this->messages));
		return $positions[$message];
	}

	private function sort() {
		if (!function_exists("cmpMessageArray")) {
			function cmpMessageArray($a, $b) {
				return $a["date"] - $b["date"];
			}
		}
		uasort($this->messages, "cmpMessageArray");
	}

	public function removeMessage($message) {
		unset($this->messages[$message->getMessageID()]);
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
	
	public function getAuthor() {
		return $this->author;
	}
	
	public function getPosts() {
		return count($this->messages);
	}

	public function isEmpty() {
		return empty($this->messages);
	}
	
	public function getLastPostMessageID() {
		return array_shift(array_slice(array_keys($this->messages),-1));
	}
	
	public function getLastPostDate() {
		return $this->messages[$this->getLastPostMessageID()]["date"];
	}
	
	public function getLastPostAuthor() {
		return $this->messages[$this->getLastPostMessageID()]["author"];
	}
	
	public function getCharset() {
		return $this->charset;
	}
}

?>
