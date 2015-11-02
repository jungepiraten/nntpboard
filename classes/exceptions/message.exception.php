<?php

class MessageException extends Exception {
	private $msg;
	private $group;

	public function __construct($msg, $group, $message = null) {
		parent::__construct($message);
		$this->group = $group;
		$this->msg = $msg;
	}
}

class InvalidMessageException extends MessageException {}
class NotFoundMessageException extends MessageException {}
class MessageIDNotMatchingMessageException extends MessageException {}

?>
