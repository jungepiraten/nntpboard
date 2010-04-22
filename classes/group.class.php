<?php

require_once(dirname(__FILE__)."/config.class.php");
require_once(dirname(__FILE__)."/connection.class.php");
require_once(dirname(__FILE__)."/thread.class.php");

class Group {
	private $host;
	private $group;
	private $username = "";
	private $password = "";

	private $datadir;

	// Alle Nachrichten als Message-Objekt
	private $messages = array();
	// Zuordnung MSGID => THREADID
	private $threadids = array();
	// Zuordnung ArtikelNr => MSGID
	private $articlenums = array();
	// Alle Threads als Thread-Objekt (ohne Nachrichten)
	private $threads = array();
	
	private $lastarticlenr = 0;

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
	
	public function open($datadir) {
		$this->datadir = $datadir;
		
		if (file_exists($this->datadir->getGroupPath($this))) {
			$data = unserialize(file_get_contents($this->datadir->getGroupPath($this)));
			$this->threadids	= $data["threadids"];
			$this->articlenums	= $data["articlenums"];
			$this->threads		= $data["threads"];

			/**
			 * Lade Threads erst nach und nach, um Weniger Last zu verursachen
			 * vgl getThread($threadid)
			 **/
		}
	}
	
	public function close() {
		if ($this->datadir === null) {
			throw new Exception("Group {$this->group} never opened.");
		}
		
		$data = array(
			"threadids"	=> $this->threadids,
			"articlenums"	=> $this->articlenums,
			"threads"	=> $this->threads);
		
		file_put_contents($this->datadir->getGroupPath($this), serialize($data));
		
		// Speichere die Nachrichten Threadweise
		foreach ($this->threads AS $threadid => $thread) {
			$messages = $thread->getMessages($this);
			// Attachments speichern
			foreach ($messages AS $message) {
				$message->saveAttachments($this->datadir);
			}
			
			$filename = $this->datadir->getThreadPath($this, $thread);
			file_put_contents($filename, serialize($messages));
		}

		$this->datadir = null;
	}
	
	public function getConnection($username = null, $password = null) {
		if ($username === null) {
			$username = $this->username;
		}
		if ($password === null) {
			$password = $this->password;
		}
		return new NNTPConnection($this, $username, $password);
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

	public function getLastArticleNr() {
		return $this->lastarticlenr;
	}
	
	public function getGroup() {
		return $this->group;
	}

	public function getThreads() {
		return $this->threads;
	}
	
	public function addThread($thread) {
		$this->threads[$thread->getThreadID()] = $thread;
	}
	
	public function getThread($threadid) {
		return $this->threads[$threadid];
	}

	public function loadThreadMessages($threadid) {
		if ($this->getThread($threadid) === null) {
			return;
		}

		if ($this->datadir === null) {
			throw new Exception("Group {$this->group} never opened.");
		}
		
		$filename = $this->datadir->getThreadPath( $this , $this->getThread($threadid) );
		if (!file_exists($filename)) {
			throw new Exception("Thread {$threadid} in Group {$this->getGroup} not yet initialized!");
		}
		$messages = unserialize(file_get_contents($filename));
		foreach ($messages AS $message) {
			$this->addMessage($message);
		}
	}

	public function getMessage($messageid) {
		if (isset($this->messages[$messageid])) {
			return $this->messages[$messageid];
		}
		$message = null;
		if (!empty($this->threadids[$messageid])) {
			$this->loadThreadMessages($this->threadids[$messageid]);
			return $this->messages[$messageid];
		}
		// TODO Nachricht direkt vom Newsserver laden
		$this->messages[$messageid] = $message;
		return $message;
	}
	
	public function getMessageByNum($num) {
		if (isset($this->articlenums[$num])) {
			return $this->getMessage($this->articlenums[$num]);
		}
		return null;
	}
	
	public function addMessage($message) {
		// Speichere die Nachricht
		$this->messages[$message->getMessageID()] = $message;
		$this->threadids[$message->getMessageID()] = $message->getThreadID();
		$this->articlenums[$message->getArticleNum()] = $message->getMessageID();

		// Ist Unterpost
		if ($message->hasParentID() && isset($this->messages[$message->getParentID()])) {
			$this->getMessage($message->getParentID())->addChild($message);
		}
		
		// Thread erstellen oder in Thread einordnen
		if (!isset($this->threads[$message->getThreadID()])) {
			$this->addThread(new Thread($message));
		}
		$this->getThread($message->getThreadID())->addMessage($message);

		// Letzte Artikelnummer updaten
		if ($message->getArticleNum() > $this->lastarticlenr) {
			$this->lastarticlenr = $message->getArticleNum();
		}
	}
}

?>
