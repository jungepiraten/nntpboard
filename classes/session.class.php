<?php

class Session {
	private $config;
	
	public function __construct($config) {
		$this->config = $config;
		session_start();
	}
	
	public function login($auth) {
		$_SESSION["auth"] = $auth;
	}

	public function logout() {
		unset($_SESSION["auth"]);
	}

	public function getAuth() {
		if (isset($_SESSION["auth"])) {
			return $_SESSION["auth"];
		}
		return $this->config->getAnonymousAuth();
	}
}

?>
