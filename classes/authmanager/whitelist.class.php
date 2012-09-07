<?php

require_once(dirname(__FILE__) . "/../authmanager.class.php");

class WhitelistAuthManager implements AuthManager {
	private $whitelist;

	public function __construct($whitelist) {
		$this->whitelist = $whitelist;
	}

	public function isAllowed(Auth $auth) {
		return ($auth != null) && in_array($auth->getUsername(), $this->whitelist);
	}
}

?>
