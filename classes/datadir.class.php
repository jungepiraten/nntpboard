<?php

require_once(dirname(__FILE__) . "/exceptions/datadir.exception.php");

// TODO Konzept der DataDir ueberdenken
class DataDir {
	private $dir;
	
	public function __construct($dir) {
		$this->dir = $dir;
	}
	
	private function getPath($filename) {
		// Lege die Verzeichnisstrukur an, soweit noetig
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
		$dirname = rtrim($this->dir, "/") . "/" . ltrim(dirname($filename), "/");
		if (!file_exists($dirname) && !mkdir_parents($dirname)) {
			throw new CreationFailedDataDirException(dirname($filename));
		}
		return rtrim($this->dir, "/") . "/" . ltrim($filename, "/");
	}
	
	
	
	private function getGroupFilename($group) {
		return ($group instanceof Group ? $group->getGroup() : $group).".dat";
	}
	
	public function getGroupPath($group) {
		return $this->getPath($this->getGroupFilename($group));
	}
	
	
	
	private function getThreadfilename($group, $thread) {
		return ($group instanceof Group ? $group->getGroup() : $group)."/threads/".md5($thread->getThreadID()).".dat";
	}
	
	public function getThreadPath($group, $part) {
		return $this->getPath($this->getThreadfilename($group, $part));
	}
	
	
	
	private function getAttachmentfilename($group, $part) {
		return ($group instanceof Group ? $group->getGroup() : $group)."/attachments/".md5($part->getMessageID())."/".$part->getFilename();
	}
	
	public function getAttachmentPath($group, $part) {
		return $this->getPath($this->getAttachmentfilename($group, $part));
	}
}

?>
