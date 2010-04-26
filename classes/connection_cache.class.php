<?php

require_once(dirname(__FILE__)."/connection.abstract.class.php");
/* Die Klassen müssen vor dem unserialize eingebunden sein, da PHP sonst
 * incomplete Objekte erstellt.
 * vgl. http://mrfoo.de/archiv/120-The-script-tried-to-execute-a-method-or-access-a-property-of-an-incomplete-object.html
 **/
require_once(dirname(__FILE__)."/address.class.php");
require_once(dirname(__FILE__)."/thread.class.php");
require_once(dirname(__FILE__)."/message.class.php");
require_once(dirname(__FILE__)."/bodypart.class.php");

class CacheConnection extends AbstractConnection {
	private $group;
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
	
	public function __construct($group, $datadir) {
		$this->group = $group;
		$this->datadir = $datadir;
	}
	
	public function open() {
		if (file_exists($this->datadir->getGroupPath($this->group))) {
			$data = unserialize(file_get_contents($this->datadir->getGroupPath($this->group)));
			$this->threadids	= $data["threadids"];
			$this->articlenums	= $data["articlenums"];
			$this->threads		= $data["threads"];
			$this->lastarticlenr	= $data["lastarticlenr"];

			/**
			 * Lade Threads erst nach und nach, um Weniger Last zu verursachen
			 * vgl loadThreadMessages($threadid)
			 **/
		}
	}

	public function close() {
		$data = array(
			"threadids"	=> $this->threadids,
			"articlenums"	=> $this->articlenums,
			"threads"	=> $this->threads,
			"lastarticlenr"	=> $this->lastarticlenr);
		
		file_put_contents($this->datadir->getGroupPath($this->group), serialize($data));
		
		// Speichere die Nachrichten Threadweise
		foreach ($this->threads AS $threadid => $thread) {
			$messageids = $thread->getMessages();
			$messages = array();
			// Attachments speichern
			foreach ($messageids AS $messageid) {
				$message = $this->getMessage($messageid);
				$message->saveAttachments($this->datadir);
				$messages[$messageid] = $message;
			}
			
			$filename = $this->datadir->getThreadPath($this->group, $thread);
			file_put_contents($filename, serialize($messages));
		}
	}

	public function getMessageByNum($num) {
		if (isset($this->articlenums[$num])) {
			return $this->getMessage($this->articlenums[$num]);
		}
		return null;
	}

	public function getMessage($messageid) {
		if (isset($this->messages[$messageid])) {
			return $this->messages[$messageid];
		}
		$message = null;
		if (!empty($this->threadids[$messageid])) {
			$this->loadThreadMessages($this->threadids[$messageid]);
			$message = $this->messages[$messageid];
		}
		$this->messages[$messageid] = $message;
		return $message;
	}

	public function getThreads() {
		return $this->threads;
	}

	public function getThread($threadid) {
		return $this->threads[$threadid];
	}

	public function getThreadCount() {
		return count($this->threads);
	}

	public function getMessagesCount() {
		return count($this->messages);
	}

	protected function getLastThread() {
		if (empty($this->threads)) {
			throw new Exception("No Thread found!");
		}
		// Wir nehmen an, dass die Threads sortiert sind ...
		return array_shift(array_slice($this->threads, 0, 1));
	}

	public function getArticleNums() {
		return array_keys($this->articlenums);
	}

	/* ****** */
	
	public function loadMessages($connection) {
		$articles = $connection->getArticleNums();
		
		foreach ($articles as $articlenr) {
			if ($articlenr > $this->getLastArticleNr()) {
				$this->addMessage($connection->getMessage($articlenr));
			}
		}
		$this->sort();
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

	private function loadThreadMessages($threadid) {
		if ($this->getThread($threadid) === null) {
			return;
		}
		
		$filename = $this->datadir->getThreadPath( $this->group , $this->getThread($threadid) );
		if (!file_exists($filename)) {
			throw new Exception("Thread {$threadid} in Group {$this->getGroup} not yet initialized!");
		}
		$messages = unserialize(file_get_contents($filename));
		foreach ($messages AS $message) {
			$this->addMessage($message);
		}
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
	
	private function addThread($thread) {
		$this->threads[$thread->getThreadID()] = $thread;
	}

	public function getLastArticleNr() {
		return $this->lastarticlenr;
	}
}

?>
