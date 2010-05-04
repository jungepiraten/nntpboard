<?php

require_once(dirname(__FILE__) . "/group.exception.php");

class MessageException extends GroupException {
	private $msg;

	public function __construct($msg, $group, $message = null) {
		parent::__construct($group, $message);
		$this->msg = $msg;
	}
}

class NotFoundMessageException extends MessageException {}
class MessageIDNotMatchingMessageException extends MessageException {}

?>
