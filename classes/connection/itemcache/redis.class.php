<?php

require_once(dirname(__FILE__) . "/../itemcache.class.php");
require_once(dirname(__FILE__) . "/redis/RedisServer.php");

class RedisItemCacheConnection extends AbstractItemCacheConnection {
	private $rediscache;
	private $link;

	public function __construct($rediscache, $uplink) {
		parent::__construct($uplink);
		$this->rediscache = $rediscache;
	}

	private function getLink() {
		if ($this->link === null) {
			$this->link = new RedisServer($this->rediscache->getHost(), $this->rediscache->getPort());
			$this->link->connect($this->rediscache->getHost(), $this->rediscache->getPort());
		}
		return $this->link;
	}

	private function get($key) {
		return unserialize($this->getLink()->Get($this->rediscache->getKeyName($key)));
	}

	private function set($key, $val) {
		$this->getLink()->Set($this->rediscache->getKeyName($key), serialize($val));
	}

	private function delete($key) {
		$this->getLink()->Del($this->rediscache->getKeyName($key));
	}

	protected function getMessageQueue($queueid) {
		$queue = $this->get("messagequeue-" . $queueid);
		if ($queue != null) {
			return $queue;
		}
		return array();
	}
	protected function setMessageQueue($queueid, $queue) {
		$this->set("messagequeue-" . $queueid, $queue);
	}

	public function loadMessageIDs() {
		return $this->get("messageid");
	}

	protected function saveMessageIDs($messageid) {
		$this->set("messageid", $messageid);
	}

	public function loadMessageThreads() {
		return $this->get("messagethreads");
	}

	protected function saveMessageThreads($messagethreads) {
		$this->set("messagethreads", $messagethreads);
	}

	public function loadMessage($messageid) {
		return $this->get("message" . md5($messageid));
	}

	protected function saveMessage($messageid, $message) {
		$this->set("message" . md5($messageid), $message);
	}

	public function removeMessage($messageid) {
		$this->delete("message" . md5($messageid));
	}

	public function loadThreadsLastPost() {
		return $this->get("threadslastpost");
	}

	protected function saveThreadsLastPost($threadids) {
		$this->set("threadslastpost", $threadids);
	}

	public function loadThread($threadid) {
		return $this->get("thread" . md5($threadid));
	}

	protected function saveThread($threadid, $thread) {
		$this->set("thread" . md5($threadid), $thread);
	}

	public function removeThread($threadid) {
		$this->delete("thread" . md5($threadid));
	}

	public function loadAcknowledges($messageid) {
		return $this->get("acks" . md5($messageid));
	}

	protected function saveAcknowledges($messageid, $acks) {
		$this->set("acks" . md5($messageid), $acks);
	}

	protected function loadGroupHash() {
		return $this->get("grouphash");
	}

	protected function saveGroupHash($hash) {
		$this->set("grouphash", $hash);
	}

	protected function loadLastThread() {
		return $this->get("lastthread");
	}

	protected function saveLastThread($thread) {
		$this->set("lastthread", $thread);
	}
}

?>
