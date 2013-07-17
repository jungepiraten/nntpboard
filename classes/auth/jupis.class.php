<?php

// http://pear.php.net/package/Net_LDAP2
require_once("Net/LDAP2.php");

require_once(dirname(__FILE__)."/anon.class.php");
require_once(dirname(__FILE__)."/file.class.php");
require_once(dirname(__FILE__)."/../exceptions/auth.exception.php");

class JuPisAnonAuth extends AnonAuth {}

class JuPisAuth extends FileUserAuth {
	protected static $MODERATORS = array("prauscher", "smrqdt", "lutoma", "c-lia");

	public function __construct($config, $username, $password) {
		parent::__construct($username, $password, new Address($username, str_replace(" ", "_", $username) . "@community.junge-piraten.de"), str_replace(" ", "_", $username), $config->getNNTPPassword());
	}

	public function mayCancel($message) {
		return parent::mayCancel($message) or in_array(strtolower($this->getUsername()), self::$MODERATORS);
	}
}

?>
