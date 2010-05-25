<?php

require_once(dirname(__FILE__) . "/groupcache.class.php");
require_once(dirname(__FILE__) . "/../exceptions/datadir.exception.php");

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

class FileCacheConnection extends AbstractGroupCacheConnection {
	private $dir;

	private $group;

	public function __construct($dir, $uplink = null) {
		parent::__construct($uplink);
		$this->dir = $dir;
	}
	
	private function getGroupFilename() {
		return $this->dir . "/index.dat";
	}
	
	/**
	 * Lade algemeine Meta-Informationen
	 **/
	public function loadGroup() {
		if (!file_exists($this->getGroupFilename())) {
			// Die Gruppe existiert noch nicht - also laden wir auch keine Posts
			return;
		}

		$filename = $this->getGroupFilename();
		$data = unserialize(file_get_contents($filename));
		if (!($data instanceof Group)) {
			throw new InvalidDatafileDataDirException($this->getGroupFilename());
		}
		return $data;
	}

	/**
	 * Speichere alle Dateien wieder ab
	 **/
	public function saveGroup($group) {
		$filename = $this->getGroupFilename();
		mkdir_parents(dirname($filename));
		file_put_contents($filename, serialize($group));
	}
}

?>
