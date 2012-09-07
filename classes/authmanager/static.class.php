<?php

require_once(dirname(__FILE__) . "/../authmanager.class.php");

class StaticAuthManager implements AuthManager {
	private $value;

	public function __construct($value) {
		$this->value = $value;
	}

	public function isAllowed(Auth $auth) {
		return $this->value;
	}
}

?>
