<?php

require_once(dirname(__FILE__) . "/../groupcache.class.php");
require_once(dirname(__FILE__) . "/../../exceptions/datadir.exception.php");

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

class FileGroupCacheConnection extends AbstractGroupCacheConnection {
	private $dir;

	private $meta = null;

	public function __construct($dir, $uplink = null) {
		parent::__construct($uplink);
		$this->dir = $dir;
	}
	
	private function getGroupFilename() {
		return $this->dir . "/index.dat";
	}
	private function getMetaFilename() {
		return $this->dir . "/meta.dat";
	}
	
	protected function loadGroup() {
		$filename = $this->getGroupFilename();
		if (!file_exists($filename)) {
			// Die Gruppe existiert noch nicht - also laden wir auch keine Posts
			return;
		}

		$data = unserialize(file_get_contents($filename));
		if (!($data instanceof Group)) {
			throw new InvalidDatafileDataDirException($this->getGroupFilename());
		}
		return $data;
	}
	protected function saveGroup($group) {
		$filename = $this->getGroupFilename();
		mkdir_parents(dirname($filename));
		file_put_contents($filename, serialize($group));
	}

	protected function loadMeta() {
		if ($this->meta == null) {
			$filename = $this->getMetaFilename();
			if (!file_exists($filename)) {
				$this->meta = array();
				return;
			}
			$this->meta = unserialize(file_get_contents($filename));
		}
	}
	protected function saveMeta() {
		if ($this->meta != null) {
			$filename = $this->getMetaFilename();
			mkdir_parents(dirname($filename));
			file_put_contents($filename, serialize($this->meta));
		}
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
