<?php

class Thread {
	private $threadid;
	private $subject;
	private $date;
	private $author;
	private $messages = array();

	public static function getByMessage($message) {
		return new Thread($message->getMessageID(), $message->getSubject(), $message->getDate(), $message->getAuthor());
	}

	public function __construct($threadid, $subject, $date, $author) {
		$this->threadid = $threadid;
		$this->subject = $subject;
		$this->date = $date;
		$this->author = $author;
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

	public function removeMessage($messageid) {
		unset($this->messages[$messageid]);
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

	public function isEmpty() {
		return $this->getPosts() <= 0;
	}

	public function getLastPostMessageID() {
		$messageids = array_keys($this->messages);
		return array_pop($messageids);
	}

	public function getLastPostDate() {
		return $this->messages[$this->getLastPostMessageID()]["date"];
	}

	public function getLastPostAuthor() {
		return $this->messages[$this->getLastPostMessageID()]["author"];
	}
}

?>
