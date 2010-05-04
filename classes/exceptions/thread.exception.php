<?php

require_once(dirname(__FILE__) . "/group.exception.php");

class ThreadException extends GroupException {
	private $thread;

	public function __construct($thread, $group, $message = null) {
		parent::__construct($group, $message);
		$this->thread = $thread;
	}
}

class NotFoundThreadException extends ThreadException {}

?>
