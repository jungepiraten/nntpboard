<?php

require_once(dirname(__FILE__)."/../connection.class.php");
/* Die Klassen müssen vor dem unserialize eingebunden sein, da PHP sonst
 * incomplete Objekte erstellt.
 * vgl. http://mrfoo.de/archiv/120-The-script-tried-to-execute-a-method-or-access-a-property-of-an-incomplete-object.html
 **/
require_once(dirname(__FILE__)."/../address.class.php");
require_once(dirname(__FILE__)."/../thread.class.php");
require_once(dirname(__FILE__)."/../message.class.php");
require_once(dirname(__FILE__)."/../bodypart.class.php");

class CacheConnection extends AbstractConnection {
	private $group;
	private $datadir;
	private $isreadonly = false;

	// MessageID => Message
	private $messages = array();
	// MessageID => ThreadID
	private $threadids = array();
	// ArtikelNum => MessageID
	private $articlenums = array();
	// ThreadID => Thread
	private $threads = array();
	// MessageID => true
	private $queue = array();

	private $lastarticlenr = 0;
	
	public function __construct($group, $datadir, $isreadonly = false) {
		$this->group = $group;
		$this->datadir = $datadir;
		$this->isreadonly = $isreadonly;
	}
	
	public function open() {
		if (file_exists($this->datadir->getGroupPath($this->group))) {
			$data = unserialize(file_get_contents($this->datadir->getGroupPath($this->group)));
			if ( !is_array($data["threadids"])
			  || !is_array($data["articlenums"])
			  || !is_array($data["threads"])
			  || !is_array($data["queue"])
			  || !is_numeric($data["lastarticlenr"]) )
			{
				throw new DataDirException("Invalid Datafile for {$this->group->getGroup()}!");
			}
			$this->threadids	= $data["threadids"];
			$this->articlenums	= $data["articlenums"];
			$this->threads		= $data["threads"];
			$this->queue		= $data["queue"];
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
			"queue"		=> $this->queue,
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

	public function hasMessageNum($num) {
		return (isset($this->articlenums[$num]) && $this->hasMessage($this->articlenums[$num]));
	}
	
	public function getMessageByNum($num) {
		if (isset($this->articlenums[$num])) {
			return $this->getMessage($this->articlenums[$num]);
		}
		return null;
	}

	public function hasMessage($messageid) {
		return isset($this->messages[$messageid]);
	}

	public function getMessage($messageid) {
		if (isset($this->messages[$messageid])) {
			return $this->messages[$messageid];
		}
		if (!empty($this->threadids[$messageid])) {
			$this->loadThreadMessages($this->threadids[$messageid]);
			return $this->messages[$messageid];
		}
		return null;
	}

	public function getThreads() {
		return $this->threads;
	}

	public function hasThread($threadid) {
		return isset($this->threads[$threadid]);
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

	public function mayPost() {
		return !$this->isreadonly;
	}

	public function post($message) {
		if (!$this->mayPost()) {
			throw new Exception("Read-Only Newsgroup");
		}
		$this->addQueueMessage($message);
		$this->sort();
	}

	/* ****** */

	public function sendMessages($connection) {
		foreach ($this->getQueueMessages() AS $messageid) {
			$message = $this->getMessage($messageid);
			// Nachricht posten und hier Löschen
			$connection->post($message);
			$this->removeMessage($message);
		}
	}
	
	public function loadMessages($connection) {
		$articles = $connection->getArticleNums();
		
		foreach ($articles as $articlenr) {
			// Lade nur neue Nachrichten
			if ($articlenr > $this->getLastArticleNr()) {
				$this->addMessage($connection->getMessage($articlenr));
			}
		}
		$this->sort();
	}
	
	/* ****** */
	
	private function sort() {
		// Sortieren
		if (!function_exists("cmpThreads")) {
			function cmpThreads($a, $b) {
				return $b->getLastPostDate() - $a->getLastPostDate();
			}
		}
		uasort($this->threads, cmpThreads);
	}

	/**
	 * Lade Nachrichten eines Threads aus einer Datei
	 **/
	private function loadThreadMessages($threadid) {
		if (!$this->hasThread($threadid)) {
			return;
		}
		
		$filename = $this->datadir->getThreadPath( $this->group , $this->getThread($threadid) );
		if (!file_exists($filename)) {
			throw new Exception("Thread {$threadid} in Group {$this->getGroup} not yet initialized!");
		}
		$messages = unserialize(file_get_contents($filename));
		if (!is_array($messages)) {
			throw new DataDirException("Invalid Threadfile for {$threadid}!");
		}
		foreach ($messages AS $message) {
			$this->addMessage($message);
		}
	}
	
	private function addMessage($message) {
		// Speichere die Nachricht (und Verweise auf selbige)
		$this->messages[$message->getMessageID()] = $message;
		$this->threadids[$message->getMessageID()] = $message->getThreadID();
		$this->articlenums[$message->getArticleNum()] = $message->getMessageID();

		// Letzte Artikelnummer updaten
		if (is_numeric($message->getArticleNum())
		  && $message->getArticleNum() > $this->lastarticlenr) {
			$this->lastarticlenr = $message->getArticleNum();
		}

		// Ist Unterpost (und Bezugspost auch vorhanden?)
		if ($message->hasParent() && $this->hasMessage($message->getParentID())) {
			$this->getMessage($message->getMessageID())->addChild($message);
		}
		
		// Thread erstellen und Nachricht in Thread einordnen
		if (!$this->hasThread($message->getThreadID())) {
			$this->addThread(new Thread($message));
		}
		$this->getThread($message->getThreadID())->addMessage($message);
	}

	private function removeMessage($message) {
		// Entferne die Nachricht und Verlinkungen auf selbige
		unset($this->messages[$message->getArticleNum()]);
		unset($this->threadids[$message->getArticleNum()]);
		unset($this->articlenums[$message->getMessageID()]);

		// TODO Entferne Verweise innerhalb der Threads und Nachrichten
		if ($message->hasParent() && $this->hasMessageNum($message->getParentID())) {
			$this->getMessage($message->getParentID())->removeChild($message);
		}
		
		if ($this->hasThread($message->getThreadID())) {
			$this->getThread();
		}
		// TODO u.U. Letzte Artikelnummer updaten
	}

	private function getQueueMessages() {
		return array_keys($this->queue);
	}

	private function addQueueMessage($message) {
		$this->addMessage($message);
		$this->queue[$message->getMessageID()] = true;
	}

	private function removeQueueMessage($message) {
		$this->removeMessage($message);
		unset($this->queue[$message->getMessageID()]);
	}
	
	private function addThread($thread) {
		$this->threads[$thread->getThreadID()] = $thread;
	}

	private function getLastArticleNr() {
		return $this->lastarticlenr;
	}
}

?>
