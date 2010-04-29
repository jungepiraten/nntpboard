<?php

// http://pear.php.net/package/Net_LDAP2
require_once("Net/LDAP2.php");

require_once(dirname(__FILE__)."/../auth.class.php");
require_once(dirname(__FILE__)."/../exceptions/auth.exception.php");

class JuPisAnonAuth extends AbstractAuth implements Auth {
	public function __construct() {
		
	}

	public function getAddress() {
		// TODO bessere adresse ausdenken *g*
		return new Address("Bernd", "bernd@example.com");
	}

	public function isUnreadThread($thread) {
		// TODO einfacher timestamp-check
		return false;
	}

	public function isUnreadGroup($group) {
		// TODO einfacher timestamp-check
		return false;
	}

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

class JuPisAuth extends JuPisAnonAuth {
	public static function authenticate($user, $pass) {
		$auth = new JuPisAuth($user, $pass);
		// fetchUserDetails() wirft eine AuthException, wenn es Probleme gab
		$auth->fetchUserDetails();
		return $auth;
	}
	
	public static function getAnonymousAuth() {
		return new JuPisAnonAuth();
	}

	private $username;
	private $password;

	public function __construct($username, $password) {
		parent::__construct();
		$this->username = $username;
		$this->password = $password;
	}

	public function getAddress() {
		return new Address($this->username, $this->username . "@community.junge-piraten.de");
	}

	public function isAnonymous() {
		return false;
	}

	public function isUnreadThread($thread) {
		// TODO wie ueberpruefen, ob ein Thread ungelesen ist? *kopfkratz*
		return false;
	}

	public function isUnreadGroup($group) {
		// TODO wie ueberpruefen, ob eine Gruppe ungelesen ist? *kopfkratz*
		return false;
	}

	public function getNNTPUsername() {
		return $this->username;
	}

	public function getNNTPPassword() {
		return $this->password;
	}

	/* ****** */

	public function fetchUserDetails() {
		$link = $this->getLDAPLink();
		// TODO mailadresse oder so holen
	}

	private function getUserDN() {
		return "uid=" . $this->username . ",ou=accounts,ou=community,o=Junge Piraten,c=DE";
	}

	private function getLDAPLink() {
		$link = Net_LDAP2::connect(array("binddn" => $this->getUserDN(), "bindpw" => $this->password, "port" => 10389) );
		if ($link instanceof PEAR_Error) {
			throw new AuthException("Login failed!");
		}
		return $link;
	}
}

?>
