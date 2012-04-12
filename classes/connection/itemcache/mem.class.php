<?php

require_once(dirname(__FILE__) . "/../itemcache.class.php");

class MemItemCacheConnection extends AbstractItemCacheConnection {
	private $memcache;

	private $link;
	
	public function __construct($memcache, $uplink) {
		parent::__construct($uplink);
		$this->memcache = $memcache;
	}

	private function getLink() {
		if ($this->link === null) {
			$this->link = new Memcache;
			$this->link->pconnect($this->memcache->getHost(), $this->memcache->getPort());
		}
		return $this->link;
	}

	protected function getMessageQueue($queueid) {
		$link = $this->getLink();
		$queue = $link->get($this->memcache->getKeyName("messagequeue-" . $queueid));
		if ($queue != null) {
			return $queue;
		}
		return array();
	}
	protected function setMessageQueue($queueid, $queue) {
		$link = $this->getLink();
		$link->set($this->memcache->getKeyName("messagequeue-" . $queueid), $queue);
	}

	public function loadMessageIDs() {
		$link = $this->getLink();
		return $link->get($this->memcache->getKeyName("messageid"));
	}
	protected function saveMessageIDs($messageid) {
		$link = $this->getLink();
		$link->set($this->memcache->getKeyName("messageid"), $messageid);
	}

	public function loadMessageThreads() {
		$link = $this->getLink();
		return $link->get($this->memcache->getKeyName("messagethreads"));
	}
	protected function saveMessageThreads($messagethreads) {
		$link = $this->getLink();
		$link->set($this->memcache->getKeyName("messagethreads"), $messagethreads);
	}

	public function loadMessage($messageid) {
		$link = $this->getLink();
		return $link->get($this->memcache->getKeyName("message" . md5($messageid)));
	}
	protected function saveMessage($messageid, $message) {
		$link = $this->getLink();
		$link->set($this->memcache->getKeyName("message" . md5($messageid)), $message);
	}
	public function removeMessage($messageid) {
		$link = $this->getLink();
		$link->delete($this->memcache->getKeyName("message" . md5($messageid)));
	}

	public function loadThreadsLastPost() {
		$link = $this->getLink();
		return $link->get($this->memcache->getKeyName("threadslastpost"));
	}
	protected function saveThreadsLastPost($threadids) {
		$link = $this->getLink();
		$link->set($this->memcache->getKeyName("threadslastpost"), $threadids);
	}

	public function loadThread($threadid) {
		$link = $this->getLink();
		return $link->get($this->memcache->getKeyName("thread" . md5($threadid)));
	}
	protected function saveThread($threadid, $thread) {
		$link = $this->getLink();
		$link->set($this->memcache->getKeyName("thread" . md5($threadid)), $thread);
	}
	public function removeThread($threadid) {
		$link = $this->getLink();
		$link->delete($this->memcache->getKeyName("thread" . md5($threadid)));
	}

	public function loadAcknowledges($messageid) {
		$link = $this->getLink();
		return $link->get($this->memcache->getKeyName("acks" . md5($messageid)));
	}
	protected function saveAcknowledges($messageid, $acks) {
		$link = $this->getLink();
		$link->set($this->memcache->getKeyName("acks" . md5($messageid)), $acks);
	}

	protected function loadGroupHash() {
		$link = $this->getLink();
		return $link->get($this->memcache->getKeyName("grouphash"));
	}
	protected function saveGroupHash($hash) {
		$link = $this->getLink();
		$link->set($this->memcache->getKeyName("grouphash"), $hash);
	}

	protected function loadLastThread() {
		$link = $this->getLink();
		return $link->get($this->memcache->getKeyName("lastthread"));
	}
	protected function saveLastThread($thread) {
		$link = $this->getLink();
		$link->set($this->memcache->getKeyName("lastthread"), $thread);
	}
}

?>
