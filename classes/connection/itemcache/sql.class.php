<?php

require_once(dirname(__FILE__) . "/../itemcache.class.php");

class SQLItemCacheConnection extends AbstractItemCacheConnection {
	private $host;
	private $user;
	private $passwort;
	private $db;
	private $prefix;
	
	private $link;
	
	public function __construct($host, $user, $passwort, $db, $prefix, $uplink) {
		parent::__construct($uplink);
	}
	
	private function getLink() {
		if ($this->link === null) {
			$this->link = new MySQLi("p:" . $this->host, $this->user, $this->passwort, $this->db);
		}
		return $this->link;
	}

	/** TODO SQL-funktionen implementieren **/
	public function loadMessageThreads() {
		return false;
	}
	protected function saveMessageThreads($messagethreads) {
	}

	public function loadMessage($messageid) {
		return false;
	}
	protected function saveMessage($messageid, $message) {
	}

	public function loadThreadIDs() {
		return false;
	}
	protected function saveThreadIDs($threadids) {
	}

	public function loadThread($threadid) {
		return false;
	}
	protected function saveThread($threadid, $thread) {
	}

	protected function loadGroupHash() {
		return false;
	}
	protected function saveGroupHash($hash) {
	}

	protected function loadLastThread() {
		return false;
	}
	protected function saveLastThread($thread) {
	}
}

?>
