<?php

class Session {
	private $config;
	
	public function __construct($config) {
		$this->config = $config;
		session_start();
	}
	
	public function login($auth) {
		if (isset($_SESSION["auth"])) {
			$auth->transferRead($_SESSION["auth"]);
		}
		$_SESSION["auth"] = $auth;
	}

	public function getAuth() {
		if (!isset($_SESSION["auth"])) {
			$this->login($this->config->getAnonymousAuth());
		}
		return $_SESSION["auth"];
	}
}

?>
