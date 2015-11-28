<?php

class ThreadException extends Exception {
	private $thread;
	private $group;

	public function __construct($thread, $group, $message = null) {
		parent::__construct($message);
		$this->group = $group;
		$this->thread = $thread;
	}
}

class NotFoundThreadException extends ThreadException {}

?>
