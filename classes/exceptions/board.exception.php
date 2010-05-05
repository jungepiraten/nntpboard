<?php

class BoardException extends Exception {
	private $boardid;

	public function __construct($boardid, $message = null) {
		parent::__construct($message);
		$this->boardid = $boardid;
	}
}

class NotFoundBoardException extends BoardException {}

?>
