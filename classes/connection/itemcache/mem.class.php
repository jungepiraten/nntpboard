<?php

require_once(dirname(__FILE__) . "/../itemcache.class.php");

class MemItemCacheConnection extends AbstractItemCacheConnection {
	private $prefix;
	private $host;
	private $port;

	private $link;
	
	public function __construct($host, $port, $prefix, $uplink) {
		parent::__construct($uplink);
		$this->host = $host;
		$this->port = $port;
		$this->prefix = $prefix;
	}

	private function getLink() {
		if ($this->link === null) {
			$this->link = new Memcache;
			$this->link->pconnect($this->host, $this->port);
		}
		return $this->link;
	}

	public function loadMessageIDs() {
		$link = $this->getLink();
		return $link->get($this->prefix . "messageid");
	}
	protected function saveMessageIDs($messageid) {
		$link = $this->getLink();
		$link->set($this->prefix . "messageid", $messageid);
	}

	public function loadMessageThreads() {
		$link = $this->getLink();
		return $link->get($this->prefix . "messagethreads");
	}
	protected function saveMessageThreads($messagethreads) {
		$link = $this->getLink();
		$link->set($this->prefix . "messagethreads", $messagethreads);
	}

	public function loadMessage($messageid) {
		$link = $this->getLink();
		return $link->get($this->prefix . "message" . md5($messageid));
	}
	protected function saveMessage($messageid, $message) {
		$link = $this->getLink();
		$link->set($this->prefix . "message" . md5($messageid), $message);
	}
	public function removeMessage($messageid) {
		$link = $this->getLink();
		$link->delete($this->prefix . "message" . md5($messageid));
	}

	public function loadThreadsLastPost() {
		$link = $this->getLink();
		return $link->get($this->prefix . "threadslastpost");
	}
	protected function saveThreadsLastPost($threadids) {
		$link = $this->getLink();
		$link->set($this->prefix . "threadslastpost", $threadids);
	}

	public function loadThread($threadid) {
		$link = $this->getLink();
		return $link->get($this->prefix . "thread" . md5($threadid));
	}
	protected function saveThread($threadid, $thread) {
		$link = $this->getLink();
		$link->set($this->prefix . "thread" . md5($threadid), $thread);
	}
	public function removeThread($threadid) {
		$link = $this->getLink();
		$link->delete($this->prefix . "thread" . md5($threadid));
	}

	public function loadAcknowledges($messageid) {
		$link = $this->getLink();
		return $link->get($this->prefix . "acks" . md5($messageid));
	}
	protected function saveAcknowledges($messageid, $acks) {
		$link = $this->getLink();
		$link->set($this->prefix . "acks" . md5($messageid), $acks);
	}

	protected function loadGroupHash() {
		$link = $this->getLink();
		return $link->get($this->prefix . "grouphash");
	}
	protected function saveGroupHash($hash) {
		$link = $this->getLink();
		$link->set($this->prefix . "grouphash", $hash);
	}

	protected function loadLastThread() {
		$link = $this->getLink();
		return $link->get($this->prefix . "lastthread");
	}
	protected function saveLastThread($thread) {
		$link = $this->getLink();
		$link->set($this->prefix . "lastthread", $thread);
	}
}

?>
