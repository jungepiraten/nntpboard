<?php

class AuthException extends Exception {
	private $username;

	public function __construct($username, $message = null) {
		parent::__construct($message);
		$this->username = $username;
	}
}

class LoginFailedAuthException extends AuthException {}

?>
