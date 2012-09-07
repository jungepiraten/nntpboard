<?php

require_once(dirname(__FILE__) . "/../authmanager.class.php");

class AllowAuthedAuthManager implements AuthManager {
	public function isAllowed(Auth $auth) {
		return ($auth != null) && ! $auth->isAnonymous();
	}
}

?>
