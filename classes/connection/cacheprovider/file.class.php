<?php

require_once(dirname(__FILE__) . "/../cacheprovider.class.php");
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

class FileCacheProvider extends AbstractCacheProvider {
	private $dir;

	// MessageID => Dummy
	private $messageids = array();
	// MessageID => Message
	private $messages = array();

	public function __construct($dir) {
		parent::__construct();
		$this->dir = $dir;
	}
	
	private function getGroupFilename() {
		return $this->dir . "/index.dat";
	}
	private function getMessageFilename($messageid) {
		return $this->dir . "/messages/" . md5($messageid) . ".dat";
	}
	
	/**
	 * Lade algemeine Meta-Informationen
	 **/
	public function open() {
		if (!file_exists($this->getGroupFilename())) {
			// Die Gruppe existiert noch nicht - also laden wir auch keine Posts
			return;
		}

		$filename = $this->getGroupFilename();
		$data = unserialize(file_get_contents($filename));
		if ( !is_array($data["messageids"]) )
		{
			throw new InvalidDatafileDataDirException($this->getGroupFilename());
		}
		$this->messageids	= $data["messageids"];
	}

	/**
	 * Speichere alle Dateien wieder ab
	 **/
	public function close() {
		$data = array(
			"messageids"	=> $this->messageids);
		$filename = $this->getGroupFilename();
		mkdir_parents(dirname($filename));
		file_put_contents($filename, serialize($data));
		
		// Speichere die Nachrichten
		foreach ($this->messages AS $messageid => $message) {
			$filename = $this->getMessageFilename($messageid);
			mkdir_parents(dirname($filename));
			file_put_contents($filename, serialize($message));
		}
	}

	/** Messages **/
	public function getMessageIDs() {
		return array_keys($this->messageids);
	}
	public function getMessageCount() {
		return count($this->messageids);
	}
	public function hasMessage($messageid) {
		return isset($this->messageids[$messageid]);
	}
	public function getMessage($messageid) {
		$filename = $this->getMessageFilename($messageid);
		if (!file_exists($filename)) {
			return;
		}
		return unserialize(file_get_contents($filename));
	}
	public function addMessage($message) {
		$this->messageids[$message->getMessageID()] = true;
		$this->messages[$message->getMessageID()] = $message;
	}

	public function removeMessage($messageid) {
		unset($this->messages[$messageid]);
		unset($this->messageids[$messageid]);
	}
}

?>
