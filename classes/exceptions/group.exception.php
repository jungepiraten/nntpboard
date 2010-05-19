<?php

class GroupException extends Exception {
	private $group;

	public function __construct($group, $message = null) {
		parent::__construct($message);
		$this->group = $group;
	}
}

class EmptyGroupException extends GroupException {}

class PostingException extends GroupException {}
class PostingNotAllowedException extends PostingException {}
class PostingFailedException extends PostingException {}

?>
