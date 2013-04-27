<?php

require_once(dirname(__FILE__) . "/../itemcache.class.php");

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

class FileItemCacheConnection extends AbstractItemCacheConnection {
	private $dir;

	private $index = null;
	
	public function __construct($dir, $uplink) {
		parent::__construct($uplink);
		$this->dir = $dir;
	}
	
	private function getMessageFilename($messageid) {
		return $this->dir . "/messages/".md5($messageid).".dat";
	}
	private function getAcknowledgesFilename($messageid) {
		return $this->dir . "/messages/".md5($messageid)."_ack.dat";
	}
	private function getThreadFilename($threadid) {
		return $this->dir . "/threads/".md5($threadid).".dat";
	}
	private function getGroupHashFilename() {
		return $this->dir . "/hash.dat";
	}
	private function getLastThreadFilename() {
		return $this->dir . "/lastthread.dat";
	}
	private function getIndexFilename() {
		return $this->dir . "/index.dat";
	}

	private function loadIndex() {
		if ($this->index == null) {
			$filename = $this->getIndexFilename();
			if (!file_exists($filename)) {
				$this->index = array();
				return;
			}
			$content = file_get_contents($filename);
			$this->index = unserialize($content);
		}
	}

	private function saveIndex() {
		if ($this->index != null) {
			$filename = $this->getIndexFilename();
			mkdir_parents(dirname($filename));
			if (file_exists($filename) && !is_writable($filename)) {
				return false;
			}
			file_put_contents($filename, serialize($this->index));
		}
	}

	public function setMessageQueue($queueid, $queue) {
		$this->loadIndex();
		$this->index['messagequeue'][$queueid] = $queue;
		$this->saveIndex();
	}
	public function getMessageQueue($queueid) {
		$this->loadIndex();
		if (isset($this->index['messagequeue'][$queueid])) {
			return $this->index['messagequeue'][$queueid];
		}
		return array();
	} 
	public function loadMessageIDs() {
		$this->loadIndex();
		if (isset($this->index['messageids'])) {
			return $this->index['messageids'];
		}
		return array();
	}
	public function saveMessageIDs($messageids) {
		$this->loadIndex();
		$this->index['messageids'] = $messageids;
		$this->saveIndex();
	}

	public function loadMessageThreads() {
		$this->loadIndex();
		if (isset($this->index['messagethreads'])) {
			return $this->index['messagethreads'];
		}
		return array();
	}
	public function saveMessageThreads($messagethreads) {
		$this->loadIndex();
		$this->index['messagethreads'] = $messagethreads;
		$this->saveIndex();
	}

	public function loadThreadsLastPost() {
		$this->loadIndex();
		if (isset($this->index['threadslastpost'])) {
			return $this->index['threadslastpost'];
		}
		return array();
	}
	public function saveThreadsLastPost($threadslastpost) {
		$this->loadIndex();
		$this->index['threadslastpost'] = $threadslastpost;
		$this->saveIndex();
	}

	public function loadMessage($messageid) {
		$filename = $this->getMessageFilename($messageid);
		if (!file_exists($filename)) {
			return;
		}
		return unserialize(file_get_contents($filename));
	}
	public function saveMessage($messageid, $message) {
		$filename = $this->getMessageFilename($messageid);
		mkdir_parents(dirname($filename));
		if (file_exists($filename) && !is_writable($filename)) {
			return false;
		}
		file_put_contents($filename, serialize($message));
	}
	public function removeMessage($messageid) {
		$filename = $this->getMessageFilename($messageid);
		if (!file_exists($filename)) {
			return;
		}
		unlink($filename);
	}

	public function loadAcknowledges($messageid) {
		$filename = $this->getAcknowledgesFilename($messageid);
		if (!file_exists($filename)) {
			return;
		}
		return unserialize(file_get_contents($filename));
	}
	public function saveAcknowledges($messageid, $acks) {
		$filename = $this->getAcknowledgesFilename($messageid);
		mkdir_parents(dirname($filename));
		if (file_exists($filename) && !is_writable($filename)) {
			return false;
		}
		file_put_contents($filename, serialize($acks));
	}

	public function loadThread($threadid) {
		$filename = $this->getThreadFilename($threadid);
		if (!file_exists($filename)) {
			return;
		}
		return unserialize(file_get_contents($filename));
	}
	public function saveThread($threadid, $thread) {
		$filename = $this->getThreadFilename($threadid);
		mkdir_parents(dirname($filename));
		if (file_exists($filename) && !is_writable($filename)) {
			return false;
		}
		file_put_contents($filename, serialize($thread));
	}
	public function removeThread($threadid) {
		$filename = $this->getMessageFilename($threadid);
		if (!file_exists($filename)) {
			return;
		}
		unlink($filename);
	}

	public function loadGroupHash() {
		$filename = $this->getGroupHashFilename();
		if (!file_exists($filename)) {
			return;
		}
		return file_get_contents($filename);
	}
	public function saveGroupHash($hash) {
		$filename = $this->getGroupHashFilename();
		mkdir_parents(dirname($filename));
		if (file_exists($filename) && !is_writable($filename)) {
			return false;
		}
		file_put_contents($filename, $hash);
	}

	public function loadLastThread() {
		$filename = $this->getLastThreadFilename();
		if (!file_exists($filename)) {
			return;
		}
		return unserialize(file_get_contents($filename));
	}
	public function saveLastThread($thread) {
		$filename = $this->getLastThreadFilename();
		mkdir_parents(dirname($filename));
		if (file_exists($filename) && !is_writable($filename)) {
			return false;
		}
		file_put_contents($filename, serialize($thread));
	}
}

?>
