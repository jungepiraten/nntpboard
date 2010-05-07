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

	// MessageID => Message
	private $messages = array();
	// MessageID => ThreadID
	private $threadids = array();
	// ThreadID => Thread
	private $threads = array();
	// MessageID => true
	private $queue = array();

	public function __construct($dir) {
		parent::__construct();
		$this->dir = $dir;
	}
	
	private function getGroupFilename() {
		return $this->dir . "/index.dat";
	}
	private function getThreadFilename($thread) {
		return $this->dir . "/threads/" . md5($thread->getThreadID()) . ".dat";
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
		if ( !is_array($data["threadids"])
		  || !is_array($data["threads"])
		  || !is_array($data["queue"]) )
		{
			throw new InvalidDatafileDataDirException($this->getGroupFilename());
		}
		$this->threadids	= $data["threadids"];
		$this->threads		= $data["threads"];
		$this->queue		= $data["queue"];

		/**
		 * Lade Threads erst nach und nach, um Weniger Last zu verursachen
		 * vgl loadThreadMessages($threadid)
		 **/
	}

	/**
	 * Lade Nachrichten eines Threads aus einer Datei
	 **/
	private function loadThreadMessages($thread) {
		$filename = $this->getThreadFilename($thread);
		if (!file_exists($filename)) {
			return;
		}
		$messages = unserialize(file_get_contents($filename));
		if (!is_array($messages)) {
			throw new InvalidDatafileDataDirException($filename);
		}
		foreach ($messages AS $messageid => $message) {
			$this->messages[$messageid] = $message;
		}
	}

	/**
	 * Speichere alle Dateien wieder ab
	 **/
	public function close() {
		$data = array(
			"threadids"	=> $this->threadids,
			"threads"	=> $this->threads,
			"queue"		=> $this->queue);
		$filename = $this->getGroupFilename();
		mkdir_parents(dirname($filename));
		file_put_contents($filename, serialize($data));
		
		// Speichere die Nachrichten Threadweise
		foreach ($this->threads AS $threadid => $thread) {
			$messageids = $thread->getMessageIDs();
			$messages = array();
			// Attachments speichern
			foreach ($messageids AS $messageid) {
				$message = $this->getMessage($messageid);
				// TODO $message->saveAttachments($this->datadir);
				$messages[$messageid] = $message;
			}
			
			$filename = $this->getThreadFilename($thread);
			mkdir_parents(dirname($filename));
			file_put_contents($filename, serialize($messages));
		}
	}

	/** Messages **/
	public function getMessageIDs() {
		return array_keys($this->threadids);
	}
	public function getMessageCount() {
		return count($this->threadids);
	}
	public function hasMessage($messageid) {
		// In der ThreadID-Liste werden alle Nachrichten-IDs gespeichert ...
		return $this->hasThread($messageid);
	}
	
	public function getMessage($messageid) {
		// Haben wir die Nachricht schon gecached?
		if (isset($this->messages[$messageid])) {
			return $this->messages[$messageid];
		}
		// Falls wir den Thread kennen, laden wir darueber die Nachrichten
		// (loadThreadMessages speichert in $messages)
		if ($this->hasThread($messageid)) {
			$this->loadThreadMessages($this->getThread($messageid));
			return $this->messages[$messageid];
		}
		return null;
	}

	public function addMessage($message, $thread) {
		$this->messages[$message->getMessageID()] = $message;
		$this->threadids[$message->getMessageID()] = $thread->getThreadID();
		$this->addThread($thread);
	}
	
	private function addThread($thread) {
		$this->threads[$thread->getThreadID()] = $thread;
	}

	public function removeMessage($messageid) {
		unset($this->messages[$messageid]);
		unset($this->threadids[$messageid]);
	}

	/** Threads **/
	public function getThreadIDs() {
		return array_keys($this->threads);
	}
	public function getThreadCount() {
		return count($this->threads);
	}
	public function hasThread($messageid) {
		return isset($this->threadids[$messageid]) && isset($this->threads[$this->threadids[$messageid]]);
	}
	public function getThread($messageid) {
		return $this->threads[$this->threadids[$messageid]];
	}

	/** Queue **/
	public function getQueue() {
		return array_keys($this->queue);
	}
	public function getQueueLength() {
		return count($this->queue);
	}
	public function hasQueued($messageid) {
		return isset($this->queue[$messageid]);
	}
	public function addToQueue($message) {
		/* Eigentlich ist egal, was im Value steht,
		 * aber durch den timestamp koennen wir noch
		 * ablesen, wie lange die Nachricht in der Queue lag */
		$this->queue[$message->getMessageID()] = time();
	}
	public function removeFromQueue($messageid) {
		unset($this->queue[$messageid]);
	}
	
	/**
	 * Sortiere die Threads nach Letztem Posting (in der Uebersicht wichtig)
	 **/
	public function sort() {
		if (!function_exists("cmpThreads")) {
			function cmpThreads($a, $b) {
				return $b->getLastPostDate() - $a->getLastPostDate();
			}
		}
		uasort($this->threads, cmpThreads);
	}
}

?>
