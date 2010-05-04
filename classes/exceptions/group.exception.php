<?php

class GroupException extends Exception {
	private $group;

	public function __construct($group, $message = null) {
		parent::__construct($message);
		$this->group = $group;
	}
}

class EmptyGroupException extends GroupException {}

class PostException extends GroupException {}
class PostingNotAllowedException extends PostException {}

?>
