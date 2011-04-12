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

	public function permanentLogin($auth) {
		$this->login($auth);
		$array = array("username" => $auth->getUsername(), "password" => $auth->getPassword());
		$string = $this->config->encryptString(serialize($array));
		setcookie("nntpboard_auth", $string, time() + 360*24*60*60);
	}

	public function logout() {
		$this->login($this->config->getAnonymousAuth());
		setcookie("nntpboard_auth", null);
	}

	public function getAuth() {
		if ((!isset($_SESSION["auth"]) || $_SESSION["auth"]->isAnonymous()) and isset($_COOKIE["nntpboard_auth"])) {
			try {
				$data = unserialize($this->config->decryptString($_COOKIE["nntpboard_auth"]));
				if (isset($data["username"]) && isset($data["password"])) {
					$this->login($this->config->getAuth($data["username"], $data["password"]));
				}
			} catch (LoginFailedAuthException $e) {}
		}
		if (!isset($_SESSION["auth"])) {
			$this->login($this->config->getAnonymousAuth());
		}
		return $_SESSION["auth"];
	}

	public function clearAttachments() {
		$_SESSION["attachments"] = array();
	}

	public function getAttachments() {
		if (isset($_SESSION["attachments"])) {
			return $_SESSION["attachments"];
		}
	}

	public function getAttachment($i) {
		if (isset($_SESSION["attachments"][$i])) {
			return $_SESSION["attachments"][$i];
		}
	}

	public function addAttachment(Attachment $a) {
		$_SESSION["attachments"][] = $a;
	}
}

?>
