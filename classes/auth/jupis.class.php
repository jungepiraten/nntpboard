<?php

// http://pear.php.net/package/Net_LDAP2
require_once("Net/LDAP2.php");

require_once(dirname(__FILE__)."/../auth.class.php");
require_once(dirname(__FILE__)."/../exceptions/auth.exception.php");

class JuPisAnonAuth extends AbstractAuth implements Auth {
	const ZEITFRIST = 10800;	// 3 Stunden
	private $readdate = null;
	private $readthreads = array();
	private $readgroups = array();

	public function __construct() {
		$this->readdate = $this->loadReadDate();
		$this->readthreads = $this->loadReadThreads();
		$this->readgroups = $this->loadReadGroups();
	}

	public function getAddress() {
		return null;
	}

	public function getReadDate() {
		return $this->readdate;
	}

	public function getReadThreads() {
		return $this->readthreads;
	}

	public function getReadGroups() {
		return $this->readgroups;
	}

	protected function loadReadDate() {
		// Alle Posts vor dem Login sind schon gelesen ;)
		return time() - self::ZEITFRIST;
	}

	protected function loadReadThreads() {
		return array();
	}

	protected function loadReadGroups() {
		return array();
	}

	protected function saveReadDate($date) {}

	protected function saveReadThread($threadid, $lastpostdate) {}

	protected function saveReadGroup($groupid, $grouphash, $threadid) {}

	protected function saveUnreadGroup($groupid, $grouphash, $threadid) {}

	public function transferRead($auth) {
		if ($auth instanceof JuPisAnonAuth) {
			$this->readdate = $auth->getReadDate();
			$this->readthreads = $auth->getReadThreads();
			$this->readgroups = $auth->getReadGroups();
		}
	}

	public function isUnreadThread($thread) {
		// Falls die Nachricht aelter als readdate ist, gilt sie als gelesen
		if ($thread->getLastPostDate() < $this->readdate) {
			return false;
		}
		// Entweder wir kennen den Thread noch gar nicht ...
		if (!isset($this->readthreads[$thread->getThreadID()])) {
			return true;
		}
		// ... oder der Timestamp hat sich veraendert
		if ($this->readthreads[$thread->getThreadID()] < $thread->getLastPostDate()) {
			return true;
		}
		return false;
	}

	public function markReadThread($thread) {
		// Trage den aktuellen Timestamp ein
		$this->readthreads[$thread->getThreadID()] = $thread->getLastPostDate();
		
		$this->saveReadThread($thread->getThreadID(), $thread->getLastPostDate());
	}

	public function generateUnreadArray($group) {
		$unreadthreads = array();
		foreach ($group->getThreadIDs() as $threadid) {
			if ($this->isUnreadThread($group->getThread($threadid))) {
				$unreadthreads[$threadid] = true;
				$this->saveUnreadGroup($group->getGroupID(), $group->getGroupHash(), $threadid);
			}
		}
		$this->readgroups[$group->getGroupID()][$group->getGroupHash()] = $unreadthreads;
	}

	public function isUnreadGroup($group) {
		if (!isset($this->readgroups[$group->getGroupID()][$group->getGroupHash()])) {
			$this->generateUnreadArray($group);
		}
		// Cache alle Thread-IDs, die in der Vergangenheit ungelesen waren
		foreach (array_keys($this->readgroups[$group->getGroupID()][$group->getGroupHash()]) as $threadid) {
			if ($this->isUnreadThread($group->getThread($threadid))) {
				return true;
			} else {
				unset($this->readgroups[$group->getGroupID()][$group->getGroupHash()][$threadid]);
				$this->saveUnreadGroup($group->getGroupID(), $group->getGroupHash(), $threadid);
			}
		}
		return false;
	}

	public function markReadGroup($group) {
		foreach ($group->getThreadIDs() as $threadid) {
			$this->markReadThread($group->getThread($threadid));
			unset($this->readgroups[$group->getGroupID()][$group->getGroupHash()][$threadid]);
		}
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
		// TODO eigentlich brauchen wir ja schon auth - aber zum testen ists einfacher so
		return $auth;
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
		return new Address($this->username, $this->getNNTPUsername() . "@community.junge-piraten.de");
	}

	public function getNNTPUsername() {
		return str_replace(" ", "_", $this->username);
	}

	public function getNNTPPassword() {
		return $this->password;
	}

	/* ****** */
	/* TODO daten von extern einholen

	protected function loadReadDate() {
		return time();
	}

	protected function loadReadThreads() {
		return array();
	}

	protected function loadReadGroups() {
		return array();
	}

	protected function saveReadDate($date) {
		
	}

	protected function saveReadThread($threadid, $lastpostdate) {
		
	}

	protected function loadReadGroups() {
		return array();
	}

	protected function saveReadGroup($groupid, $grouphash, $threadid) {
		
	}

	protected function saveUnreadGroup($groupid, $grouphash, $threadid) {
		
	}
	*/

	/* ****** */

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
