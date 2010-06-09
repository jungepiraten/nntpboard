<?php

require_once(dirname(__FILE__) . "/../itemcache.class.php");

abstract class AbstractSQLItemCacheConnection extends AbstractItemCacheConnection {
	private $prefix;
	
	private $link;
	
	public function __construct($prefix, $uplink) {
		parent::__construct($uplink);
		$this->prefix = $prefix;
	}
	
	abstract protected function query($sql);

	/** TODO SQL-funktionen implementieren **/
	public function loadMessageThreads() {
		return array();
	}
	protected function saveMessageThreads($messagethreads) {
	}

	public function loadMessage($messageid) {
		return null;
	}
	protected function saveMessage($messageid, $message) {
	}

	public function loadThreadsLastPost() {
		return array();
	}
	protected function saveThreadsLastPost($threadids) {
	}

	public function loadThread($threadid) {
		return null;
	}
	protected function saveThread($threadid, $thread) {
	}

	protected function loadGroupHash() {
		return null;
	}
	protected function saveGroupHash($hash) {
	}

	protected function loadLastThread() {
		return null;
	}
	protected function saveLastThread($thread) {
	}
}

?>
