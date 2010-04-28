<?php

require_once(dirname(__FILE__)."/../auth.class.php");

class JuPisAuth extends AbstractAuth implements Auth {
	public static function authenticate($user, $pass) {
		// TODO logincheck?
		//throw new LoginException("Login failed!");
		return new JuPisAuth($user, $pass);
	}
	
	public static function getAnonymousAuth() {
		return new JuPisAnonAuth();
	}

	private $username;
	private $password;

	public function __construct($username, $password) {
		$this->username = $username;
		$this->password = $password;
	}

	public function getAddress() {
		return new Address($this->username, $this->username . "@community.junge-piraten.de");
	}

	public function isAnonymous() {
		return false;
	}

	public function getNNTPUsername() {
		return $this->username;
	}

	public function getNNTPPassword() {
		return $this->password;
	}
}

class JuPisAnonAuth extends JuPisAuth {
	public function __construct() {}

	public function isAnonymous() {
		return true;
	}

	public function getNNTPUsername() {
		return null;
	}

	public function getNNTPPassword() {
		return null;
	}
}

?>
