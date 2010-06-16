<?php

// http://pear.php.net/package/Net_LDAP2
require_once("Net/LDAP2.php");

require_once(dirname(__FILE__)."/../auth.class.php");
require_once(dirname(__FILE__)."/../exceptions/auth.exception.php");

class JuPisAnonAuth extends AbstractAuth implements Auth {
	public function __construct() {
		parent::__construct();
	}

	public function getAddress() {
		return null;
	}

	public function getNNTPUsername() {
		return null;
	}

	public function getNNTPPassword() {
		return null;
	}
}

if (!function_exists("mkdir_parents")) {
	function mkdir_parents($dir) {
		if (!file_exists($dir)) {
			if (!file_exists(dirname($dir))) {
				mkdir_parents(dirname($dir));
			}
			return mkdir($dir);
		}
	}
}

class JuPisAuth extends JuPisAnonAuth {
	public static function authenticate($user, $pass) {
		$auth = new JuPisAuth($user, $pass);
		// TODO eigentlich brauchen wir ja schon auth - aber zum testen ists einfacher so
		return $auth;
		// fetchUserDetails() wirft eine AuthException, wenn es Probleme gab
		$auth->fetchUserDetails();
		return $auth;
	}

	private $username;
	private $password;

	private $data;

	public function __construct($username, $password) {
		parent::__construct();
		$this->username = $username;
		$this->password = $password;
	}

	public function getAddress() {
		return new Address($this->username, $this->getNNTPUsername() . "@community.junge-piraten.de");
	}

	public function getNNTPUsername() {
		return str_replace(" ", "_", $this->username);
	}

	public function getNNTPPassword() {
		return $this->password;
	}

	private function getFilename() {
		return dirname(__FILE__) . "/jupis/" . $this->getNNTPUsername() . ".dat";
	}

	private function loadData() {
		$filename = $this->getFilename();
		if (!file_exists($filename)) {
			// Die Gruppe existiert noch nicht - also laden wir auch keine Posts
			$this->data = array();
			return;
		}
		$this->data = unserialize(file_get_contents($this->getFilename()));
	}

	private function saveData() {
		$filename = $this->getFilename();
		mkdir_parents(dirname($filename));
		file_put_contents($filename, serialize($this->data));
	}
	
	protected function loadReadDate() {
		if (!isset($this->data)) {
			$this->loadData();
		}
		if (isset($this->data["readdate"])) {
			return $this->data["readdate"];
		}
		return parent::loadReadDate();
	}

	protected function loadReadThreads() {
		if (!isset($this->data)) {
			$this->loadData();
		}
		if (isset($this->data["readthreads"])) {
			return $this->data["readthreads"];
		}
		return parent::loadReadThreads();
	}

	protected function loadReadGroups() {
		if (!isset($this->data)) {
			$this->loadData();
		}
		if (isset($this->data["readgroups"])) {
			return $this->data["readgroups"];
		}
		return parent::loadReadGroups();
	}

	protected function saveReadDate($date) {
		if (!isset($this->data)) {
			$this->loadData();
		}
		$this->data["readdate"] = $date;
		$this->saveData();
	}

	protected function saveReadThreads($data) {
		if (!isset($this->data)) {
			$this->loadData();
		}
		$this->data["readthreads"] = $data;
		$this->saveData();
	}

	protected function saveReadGroups($data) {
		if (!isset($this->data)) {
			$this->loadData();
		}
		$this->data["readgroups"] = $data;
		$this->saveData();
	}

	public function fetchUserDetails() {
		// Versuchen wir uns mal anzumelden
		$link = $this->getLDAPLink();
		$link->done();
	}

	private function getUserDN() {
		// Escape "gefaehrliche" Teile
		return "uid=" . preg_replace('/(,|=|\+|<|>|\\\\|"|#)/e', '"\\\\\\".str_pad(dechex(ord("$1")),2,"0")', $this->username) . ",ou=accounts,ou=community,o=Junge Piraten,c=DE";
	}

	private function getLDAPLink() {
		$link = Net_LDAP2::connect(array("binddn" => $this->getUserDN(), "bindpw" => $this->password, "port" => 389) );
		if ($link instanceof PEAR_Error) {
			throw new LoginFailedAuthException($this->username);
		}
		return $link;
	}
}

?>
