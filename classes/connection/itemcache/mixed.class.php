<?php

require_once(dirname(__FILE__) . "/../itemcache.class.php");

class MixedItemCacheConnection extends AbstractItemCacheConnection {
	private $upstreams = array();

	public function __construct($upstreams) {
		parent::__construct();
		$this->upstreams = $upstreams;
	}

	private function getConnection($labels) {
		$labels[] = "default";
		foreach ($labels as $label) {
			if (isset($this->upstreams[$label])) {
				return $this->upstreams[$label];
			}
		}
		throw new Exception("No Connection found for " . $label);
	}

	/** Write-Commands must be broadcasted **/
	private function broadcastCommand($command, $args) {
		foreach ($this->upstreams as $upstream) {
			call_user_func_array(array($upstream, $command), $args);
		}
	}

	public function getMessageQueue($queueid) {
		return $this->getConnection(array("queue"))->getMessageQueue($queueid);
	}
	public function setMessageQueue($queueid, $queue) {
		return $this->getConnection(array("queue"))->setMessageQueue($queueid, $queue);
	}

	public function loadMessageIDs() {
		return $this->getConnection(array("messageids","index"))->loadMessageIDs();
	}
	public function saveMessageIDs($messageids) {
		return $this->getConnection(array("messageids","index"))->saveMessageIDs($messageids);
	}
	public function loadMessageThreads() {
		return $this->getConnection(array("messagethreads","index"))->loadMessageThreads();
	}
	public function saveMessageThreads($messagethreads) {
		return $this->getConnection(array("messagethreads","index"))->saveMessageThreads($messagethreads);
	}
	public function loadThreadsLastPost() {
		return $this->getConnection(array("lastpost","index"))->loadThreadsLastPost();
	}
	public function saveThreadsLastPost($messageids) {
		return $this->getConnection(array("lastpost","index"))->saveThreadsLastPost($messageids);
	}
	public function loadMessage($messageid) {
		return $this->getConnection(array("message"))->loadMessage($messageid);
	}
	public function saveMessage($messageid, $message) {
		return $this->getConnection(array("message"))->saveMessage($messageid, $message);
	}
	public function removeMessage($messageid) {
		return $this->getConnection(array("message"))->removeMessage($messageid);
	}
	public function loadThread($threadid) {
		return $this->getConnection(array("thread"))->loadThread($threadid);
	}
	public function saveThread($threadid, $thread) {
		return $this->getConnection(array("thread"))->saveThread($threadid, $thread);
	}
	public function removeThread($threadid) {
		return $this->getConnection(array("thread"))->removeThread($threadid);
	}
	public function loadAcknowledges($messageid) {
		return $this->getConnection(array("acknowledges","message"))->loadAcknowledges($messageid);
	}
	public function saveAcknowledges($messageid, $acks) {
		return $this->getConnection(array("acknowledges","message"))->saveAcknowledges($messageid, $acks);
	}
	public function loadGroupHash()	{
		return $this->getConnection(array("grouphash","index"))->loadGroupHash();
	}
	public function saveGroupHash($hash) {
		return $this->getConnection(array("grouphash","index"))->saveGroupHash($hash);
	}
	public function loadLastThread() {
		return $this->getConnection(array("lastthread","index"))->loadLastThread();
	}
	public function saveLastThread($thread) {
		return $this->getConnection(array("lastthread","index"))->saveLastThread($thread);
	}
}
