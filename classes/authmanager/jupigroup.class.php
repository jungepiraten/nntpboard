<?php

require_once(dirname(__FILE__) . "/../authmanager.class.php");

class AllowJuPiGroupAuthManager implements AuthManager {
	private $group;

	public function __construct($group) {
		$this->group = $group;
	}

	public function isAllowed(Auth $auth) {
		if (!($auth instanceof JuPisAuth)) {
			return false;
		}
		return $auth->isInGroup($this->group);
	}
}

?>
