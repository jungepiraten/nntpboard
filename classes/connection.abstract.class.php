<?php

interface iConnection {
	public function open();
	public function close();

	public function getThreadCount();
	public function getMessagesCount();

	public function getThreads();
	public function getArticleNums();
	public function getMessageByNum($num);
	public function getMessage($msgid);
	public function getThread($threadid);
	
	public function getLastPostMessageID();
	public function getLastPostSubject($charset = null);
	public function getLastPostDate();
	public function getLastPostAuthor($charset = null);
	public function getLastPostThreadID();
}

abstract class AbstractConnection implements iConnection {
	abstract protected function getLastThread();

	public function getLastPostMessageID() {
		return $this->getLastThread()->getLastPostMessageID();
	}

	public function getLastPostSubject($charset = null) {
		return $this->getLastThread()->getSubject();
	}

	public function getLastPostDate() {
		return $this->getLastThread()->getLastPostDate();
	}

	public function getLastPostAuthor($charset = null) {
		return $this->getLastThread()->getLastPostAuthor();
	}

	public function getLastPostThreadID() {
		return $this->getLastThread()->getThreadID();
	}
}

?>
