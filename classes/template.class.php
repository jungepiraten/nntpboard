<?php

interface Template {
	public function viewexception($exception);

	public function viewboard($board, $group, $page = 0, $pages = 0, $threads = null, $mayPost = false);
	public function viewthread($board, $thread, $page, $pages, $messages, $mayPost = false);
	public function viewmessage($board, $thread, $message, $mayPost = false);

	public function viewpostform($board, $reference = null, $quote = false);
	public function viewpostsuccess($board, $thread, $message);
	public function viewpostmoderated($board, $thread, $message);

	public function viewacknowledgesuccess($board, $thread, $message, $acknowledge);
	public function viewacknowledgemoderated($board, $thread, $message, $acknowledge);

	public function viewcancelsuccess($board, $thread, $message, $cancel);
	public function viewcancelmoderated($board, $thread, $message, $cancel);

	public function viewloginform();
	public function viewloginfailed();
	public function viewloginsuccess($auth);
	public function viewlogoutsuccess();
	public function viewuserpanel();
}

abstract class AbstractTemplate implements Template {
	private $config;
	private $charset;
	private $auth;
	private $threadsperpage;
	private $messagesperpage;

	public function __construct($config, $charset, $auth, $threadsperpage = null, $messagesperpage = null) {
		$this->config = $config;
		$this->charset = $charset;
		$this->auth = $auth;
		$this->threadsperpage = $threadsperpage;
		$this->messagesperpage = $messagesperpage;
	}

	protected function getConfig() {
		return $this->config;
	}

	protected function getCharset() {
		return $this->charset;
	}

	protected function getAuth() {
		return $this->auth;
	}
}

?>
