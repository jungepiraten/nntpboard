<?php

require_once(dirname(__FILE__) . "/../cache.class.php");

abstract class KeyValueCacheConnection extends AbstractCacheConnection {
	public function __construct($uplink) {
		parent::__construct($uplink);
	}

	abstract protected function get($key);
	abstract protected function set($key, $val);
	abstract protected function delete($key);

	public function getMessageQueue($queueid) {
		$queue = $this->get("messagequeue-" . $queueid);
		if ($queue != null) {
			return $queue;
		}
		return array();
	}
	public function setMessageQueue($queueid, $queue) {
		$this->set("messagequeue-" . $queueid, $queue);
	}

	public function loadMessageIDs() {
		return $this->get("messageid");
	}

	public function saveMessageIDs($messageid) {
		$this->set("messageid", $messageid);
	}

	public function loadMessageThreads() {
		return $this->get("messagethreads");
	}

	public function saveMessageThreads($messagethreads) {
		$this->set("messagethreads", $messagethreads);
	}

	public function loadMessage($messageid) {
		return $this->get("message" . md5($messageid));
	}

	public function saveMessage($messageid, $message) {
		$this->set("message" . md5($messageid), $message);
	}

	public function removeMessage($messageid) {
		$this->delete("message" . md5($messageid));
	}

	public function loadThreadsLastPost() {
		return $this->get("threadslastpost");
	}

	public function saveThreadsLastPost($threadids) {
		$this->set("threadslastpost", $threadids);
	}

	public function loadThread($threadid) {
		return $this->get("thread" . md5($threadid));
	}

	public function saveThread($threadid, $thread) {
		$this->set("thread" . md5($threadid), $thread);
	}

	public function removeThread($threadid) {
		$this->delete("thread" . md5($threadid));
	}

	public function loadAcknowledges($messageid) {
		return $this->get("acks" . md5($messageid));
	}

	public function saveAcknowledges($messageid, $acks) {
		$this->set("acks" . md5($messageid), $acks);
	}

	public function loadGroupHash() {
		return $this->get("grouphash");
	}

	public function saveGroupHash($hash) {
		$this->set("grouphash", $hash);
	}
}

?>
