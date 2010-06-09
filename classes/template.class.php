<?php

interface Template {
	public function viewexception($exception);

	public function viewboard($board, $group, $page = 0, $pages = 0, $threads = null, $mayPost = false);
	public function viewthread($board, $thread, $page, $pages, $messages, $mayPost = false);
	public function viewmessage($board, $thread, $message, $mayPost = false);

	public function viewpostform($board, $reference = null);
	public function viewpostsuccess($board, $thread, $message);
	public function viewpostmoderated($board, $thread, $message);

	public function viewloginform();
	public function viewloginfailed();
	public function viewloginsuccess($auth);
	public function viewlogoutsuccess();
	public function viewuserpanel();
}

abstract class AbstractTemplate implements Template {
	private $charset;
	private $auth;
	private $threadsperpage;
	private $messagesperpage;

	public function __construct($charset, $auth, $threadsperpage = null, $messagesperpage = null) {
		$this->charset = $charset;
		$this->auth = $auth;
		$this->threadsperpage = $threadsperpage;
		$this->messagesperpage = $messagesperpage;
	}

	protected function getCharset() {
		return $this->charset;
	}

	protected function getAuth() {
		return $this->auth;
	}

	protected function getThreadsPerPage() {
		return $this->threadsperpage === null ? 20 : $this->threadsperpage;
	}

	protected function getMessagesPerPage() {
		return $this->messagesperpage === null ? 10 : $this->messagesperpage;
	}
}

?>
