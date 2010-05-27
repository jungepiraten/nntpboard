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

	private $meta = null;
	
	public function __construct($dir, $uplink) {
		parent::__construct($uplink);
		$this->dir = $dir;
	}
	
	private function getMessageFilename($messageid) {
		return $this->dir . "/messages/".md5($messageid).".dat";
	}
	private function getThreadFilename($threadid) {
		return $this->dir . "/threads/".md5($threadid).".dat";
	}
	private function getMetaFilename() {
		return $this->dir . "/meta.dat";
	}

	private function loadMeta() {
		if ($this->meta == null) {
			$filename = $this->getMetaFilename();
			if (!file_exists($filename)) {
				$this->meta = array();
				return;
			}
			$this->meta = unserialize(file_get_contents($filename));
		}
	}

	private function saveMeta() {
		if ($this->meta != null) {
			$filename = $this->getMetaFilename();
			mkdir_parents(dirname($filename));
			file_put_contents($filename, serialize($this->meta));
		}
	}

	public function loadMessageThreads() {
		$this->loadMeta();
		if (isset($this->meta['messagethreads'])) {
			return $this->meta['messagethreads'];
		}
		return array();
	}
	protected function saveMessageThreads($messagethreads) {
		$this->meta['messagethreads'] = $messagethreads;
		$this->saveMeta();
	}

	public function loadMessage($messageid) {
		$filename = $this->getMessageFilename($messageid);
		if (!file_exists($filename)) {
			return;
		}
		return unserialize(file_get_contents($filename));
	}
	protected function saveMessage($messageid, $message) {
		$filename = $this->getMessageFilename($messageid);
		mkdir_parents(dirname($filename));
		file_put_contents($filename, serialize($message));
	}

	public function loadThreadIDs() {
		$this->loadMeta();
		if (isset($this->meta['threadids'])) {
			return $this->meta['threadids'];
		}
		return array();
	}
	protected function saveThreadIDs($threadids) {
		$this->meta['threadids'] = $threadids;
		$this->saveMeta();
	}

	public function loadThread($threadid) {
		$filename = $this->getThreadFilename($threadid);
		if (!file_exists($filename)) {
			return;
		}
		return unserialize(file_get_contents($filename));
	}
	protected function saveThread($threadid, $thread) {
		$filename = $this->getThreadFilename($threadid);
		mkdir_parents(dirname($filename));
		file_put_contents($filename, serialize($thread));
	}

	protected function loadGroupHash() {
		$this->loadMeta();
		if (isset($this->meta['hash'])) {
			return $this->meta['hash'];
		}
	}
	protected function saveGroupHash($hash) {
		$this->meta['hash'] = $hash;
		$this->saveMeta();
	}

	protected function loadLastThread() {
		$this->loadMeta();
		if (isset($this->meta['lastthread'])) {
			return $this->meta['lastthread'];
		}
	}
	protected function saveLastThread($thread) {
		$this->meta['lastthread'] = $thread;
		$this->saveMeta();
	}
}

?>
