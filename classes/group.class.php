<?php

require_once(dirname(__FILE__)."/config.class.php");
require_once(dirname(__FILE__)."/connection.class.php");
require_once(dirname(__FILE__)."/thread.class.php");

class Group {
	private $host;
	private $group;
	private $username = "";
	private $password = "";

	// Zuordnung MSGID => THREADID
	private $messages = array();
	// Alle Threads als Thread-Objekt (ohne Nachrichten)
	private $threads = array();

	private $threadcache = array();

	public function __construct(Host $host, $group, $username = "", $password = "") {
		$this->host = $host;
		$this->group = $group;
		$this->username = $username;
		$this->password = $password;
	}
	
	public function getHost() {
		return $this->host;
	}

	public function setAuth($username, $password) {
		$this->username = $username;
		$this->password = $password;
	}

	public function sendMessage($message) {
		// TODO
	}
	
	// Lade Zwischenstand
	public function load() {
		global $config;
		
		if (!file_exists($config->getDataDir()->getGroupPath($this))) {
			throw new Exception("Group ".$this->group." not yet initialized.");
		}
		
		$data = unserialize(file_get_contents($config->getDataDir()->getGroupPath($this)));
		$this->messages	= $data["messages"];
		$this->threads	= $data["threads"];
		
		// Im gespeicherten "eingefrorenen" Zustand wird die Zuordnung zur Gruppe nicht gespeichert
		foreach ($this->threads AS &$thread) {
			$thread->setGroup($this);
		}

		/**
		 * Lade Threads erst nach und nach, um Weniger Last zu verursachen
		 * vgl getThread($threadid)
		 **/
	}
	
	// Speichere Zwischenstand
	public function save() {
		global $config;
		
		$data = array(
			"messages"	=> $this->messages,
			"threads"	=> $this->threads);
		
		// Objekte "einfrieren", um Platz zu sparen
		foreach ($data["threads"] AS &$thread) {
			$thread->setGroup(null);
		}
		
		file_put_contents($config->getDatadir()->getGroupPath($this), serialize($data));
		
		// Speichere die Nachrichten Threadweise
		foreach ($this->threadcache AS $threadid => $messages) {
			// Attachments speichern (und gleichzeitig die Objekte entlasten)
			foreach ($messages AS &$message) {
				$message->saveAttachments($config->getDataDir());
				// Komprimiere das Message-Objekt (Speicher sparen)
				$message->setGroup(null);
			}
			
			$filename = $config->getDatadir()->getThreadPath($this, $this->getThread($threadid));
			file_put_contents($filename, serialize($messages));
		}
	}
	
	public function getConnection($username = null, $password = null) {
		if ($username === null) {
			$username = $this->username;
		}
		if ($password === null) {
			$password = $this->password;
		}
		return new IMAPConnection($this, $username, $password);
	}
	
	public function clear() {
		$this->threadcache = array();
		$this->messages = array();
		$this->threads = array();
	}
	
	public function sort() {
		// Sortieren
		if (!function_exists("cmpThreads")) {
			function cmpThreads($a, $b) {
				return $b->getLastPostDate() - $a->getLastPostDate();
			}
		}
		uasort($this->threads, cmpThreads);
	}

	public function getThreadCount() {
		return count($this->threads);
	}

	public function getMessagesCount() {
		return count($this->messages);
	}

	private function getLastThread() {
		if (empty($this->threads)) {
			throw new Exception("No Thread found!");
		}
		// Wir nehmen an, dass die Threads sortiert sind ...
		return array_shift(array_slice($this->threads, 0, 1));
	}

	public function getLastPostMessageID() {
		try {
			return $this->getLastThread()->getLastPostMessageID();
		} catch (Exception $e) {
			return null;
		}
	}

	public function getLastPostSubject() {
		try {
			return $this->getLastThread()->getSubject();
		} catch (Exception $e) {
			return null;
		}
	}

	public function getLastPostDate() {
		try {
			return $this->getLastThread()->getLastPostDate();
		} catch (Exception $e) {
			return null;
		}
	}

	public function getLastPostAuthor() {
		try {
			return $this->getLastThread()->getLastPostAuthor();
		} catch (Exception $e) {
			return null;
		}
	}
	
	public function getLastPostThreadID() {
		try {
			return $this->getLastThread()->getThreadID();
		} catch (Exception $e) {
			return null;
		}
	}
	
	public function getGroup() {
		return $this->group;
	}

	public function getThreads() {
		return $this->threads;
	}
	
	public function addThread($thread) {
		$this->threads[$thread->getThreadID()] = $thread;
		$this->threadcache[$thread->getThreadID()] = array();
	}
	
	public function getThread($threadid) {
		return $this->threads[$threadid];
	}

	public function getThreadMessages($threadid) {
		global $config;
	
		// Kleines Caching - vermutlich manchmal sinnvoll ;)
		if (!isset($this->threadcache[$threadid])) {
			if ($this->getThread($threadid) === null) {
				return null;
			}
			$filename = $config->getDataDir()->getThreadPath( $this , $this->getThread($threadid) );
			if (!file_exists($filename)) {
				throw new Exception("Thread {$threadid} in Group {$this->getGroup} not yet initialized!");
			}
			$this->threadcache[$threadid] = unserialize(file_get_contents($filename));
			// Im gespeicherten ("eingefrorenen") Zustand wird die Zuordnung zur Gruppe nicht gespeichert
			foreach ($this->threadcache[$threadid] AS &$message) {
				$message->setGroup($this);
			}
		}
		return $this->threadcache[$threadid];
	}
	
	public function addMessage($message) {
		// Verlinkung message => thread
		$this->messages[$message->getMessageID()] = $message->getThreadID();
		
		// Ist Unterpost
		if ($message->hasParentID() && isset($this->messages[$message->getParentID()])) {
			$this->getMessage($message->getParentID())->addChild($message);
		}
		
		// Thread erstellen oder in Thread einordnen
		if (!isset($this->threads[$message->getThreadID()])) {
			$this->addThread(new Thread($message));
		} else {
			$this->getThread($message->getThreadID())->addMessage($message);
		}
		
		// Trage Nachricht in den Cache ein
		$this->threadcache[$message->getThreadID()][$message->getMessageID()] = $message;
	}

	public function getMessage($messageid) {
		// Suche Passende ThreadID
		$thread = $this->getThreadMessages($this->messages[$messageid]);
		return $thread[$messageid];
	}
}

?>
