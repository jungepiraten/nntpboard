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
require_once(dirname(__FILE__)."/../exceptions/group.exception.php");
require_once(dirname(__FILE__)."/../exceptions/thread.exception.php");
require_once(dirname(__FILE__)."/../exceptions/message.exception.php");
require_once(dirname(__FILE__)."/../exceptions/datadir.exception.php");

class CacheConnection extends AbstractConnection {
	private $group;
	private $auth;
	private $datadir;

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
	
	public function __construct($group, $auth, $datadir) {
		parent::__construct();
		
		$this->group = $group;
		$this->auth = $auth;
		$this->datadir = $datadir;
	}
	
	public function open() {
		if (file_exists($this->datadir->getGroupPath($this->group))) {
			$filename = $this->datadir->getGroupPath($this->group);
			$data = unserialize(file_get_contents($filename));
			if ( !is_array($data["threadids"])
			  || !is_array($data["articlenums"])
			  || !is_array($data["threads"])
			  || !is_array($data["queue"]) )
			{
				throw new InvalidDatafileDataDirException($this->datadir->getGroupPath($this->group));
			}
			$this->threadids	= $data["threadids"];
			$this->articlenums	= $data["articlenums"];
			$this->threads		= $data["threads"];
			$this->queue		= $data["queue"];

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
			"queue"		=> $this->queue);
		
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
		// Die einzige Moeglichkeit besteht darin, die MessageID herauszufinden
		if (isset($this->articlenums[$num])) {
			return $this->getMessage($this->articlenums[$num]);
		}
		throw new NotFoundMessageException($num, $this->group);
	}

	public function hasMessage($messageid) {
		return isset($this->messages[$messageid]);
	}

	public function getMessage($messageid) {
		// Haben wir die Nachricht schon gecached?
		if (isset($this->messages[$messageid])) {
			return $this->messages[$messageid];
		}
		// Falls wir den Thread kennen, laden wir darueber die Nachrichten
		// (loadThreadMessages speichert in $messages)
		if (!empty($this->threadids[$messageid])) {
			$this->loadThreadMessages($this->threadids[$messageid]);
			return $this->messages[$messageid];
		}
		throw new NotFoundMessageException($messageid, $this->group);
	}

	public function hasThread($threadid) {
		return isset($this->threads[$threadid]);
	}

	public function getThread($threadid) {
		return $this->threads[$threadid];
	}

	public function getThreads() {
		return $this->threads;
	}

	public function getThreadCount() {
		return count($this->threads);
	}

	public function getMessagesCount() {
		return count($this->messages);
	}

	protected function getLastThread() {
		if (empty($this->threads)) {
			throw new EmptyGroupException($this->group);
		}
		// Wir nehmen an, dass die Threads sortiert sind ...
		return array_shift(array_slice($this->threads, 0, 1));
	}

	/**
	 * Gebe eine Liste aller ArtikelNummern zurueck
	 **/
	public function getArticleNums() {
		return array_keys($this->articlenums);
	}

	/**
	 * Poste eine Nachricht
	 * Dafuer nutzen wir eine Queue, die beim naechsten sync (cron.php) mittels
	 * sendMessages() abgearbeitet wird.
	 * Solange ist die Nachricht nur im Forum sichtbar.
	 **/
	public function post($message) {
		if (!$this->group->mayPost($this->auth)) {
			throw new PostingNotAllowedException();
		}
		// Moderierte Nachrichten kommen via NNTP rein (direkt ueber addMessage)
		if ($this->group->isModerated()) {
			return;
		}
		$this->addQueueMessage($message);
		$this->sort();
	}

	/**
	 * Poste Queue-Nachrichten auf $connection
	 **/
	public function sendMessages($connection) {
		foreach ($this->getQueue() AS $messageid) {
			// Nachricht laden
			$message = $this->getMessage($messageid);
			// Nachricht posten und hier Löschen
			$connection->post($message);
			$this->removeMessage($message);
		}
	}
	
	/**
	 * Hole neue Daten von $connection
	 **/
	public function loadMessages($connection) {
		$articles = $connection->getArticleNums();
		// Wenn die hoechste ArtikelNum sich nicht veraendert hat, hat sich gar nix getan (spart sortieren)
		if (max($articles) <= max($this->getArticleNums())) {
			return;
		}
		
		foreach ($articles as $articlenr) {
			// Lade nur neue Nachrichten
			if (!$this->hasMessageNum($articlenr)) {
				$this->addMessage($connection->getMessageByNum($articlenr));
			}
		}
		$this->sort();
	}
	
	/**
	 * Sortiere die Threads nach Letztem Posting (in der Uebersicht wichtig)
	 **/
	private function sort() {
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
			throw new NotFoundThreadException($threadid, $this->getGroup());
		}
		$messages = unserialize(file_get_contents($filename));
		if (!is_array($messages)) {
			throw new InvalidDatafileDataDirException($filename);
		}
		foreach ($messages AS $message) {
			$this->addMessage($message);
		}
	}
	
	/**
	 * Fuege eine Nachricht ein und referenziere sie
	 * TODO referenzieren outsourcen?
	 **/
	private function addMessage($message) {
		// Speichere die Nachricht (und Verweise auf selbige)
		$this->messages[$message->getMessageID()] = $message;
		$this->threadids[$message->getMessageID()] = $message->getThreadID();
		$this->articlenums[$message->getArticleNum()] = $message->getMessageID();

		// Ist Unterpost (und Bezugspost auch vorhanden?)
		if ($message->hasParent() && $this->hasMessage($message->getParentID())) {
			$this->getMessage($message->getMessageID())->addChild($message);
		}
		
		// Thread erstellen und Nachricht in Thread einordnen
		if (!$this->hasThread($message->getThreadID())) {
			$this->addThread(new Thread($message));
		}
		$this->getThread($message->getThreadID())->addMessage($message);

		/**
		 * Wenn wir die Nachricht vom NNTP bekommen haben, koennen wir ihn aus der Queue streichen
		 * Wenn diese Nachricht aus der Queue kommt, wird sie gleich in die Queue eingetragen
		 * => Einfach aus der Queue streichen, falls noetig wirds wieder eingetragen
		 */
		if ($this->hasQueued($message->getMessageID())) {
			$this->removeQueue($message->getMessageID());
		}
	}

	/**
	 * Loesche die Nachricht mit allen Referenzen
	 **/
	private function removeMessage($messageid) {
		$message = $this->getMessage($messageid);

		// Entferne die Nachricht und Verlinkungen auf selbige
		unset($this->messages[$message->getArticleNum()]);
		unset($this->threadids[$message->getArticleNum()]);
		unset($this->articlenums[$message->getMessageID()]);

		// Unterelemente auf das Vaterelement schieben
		foreach ($message->getChilds() AS $messageid) {
			$this->getMessage($messageid)->setParentID($message->hasParentID() ? $message->getParentID() : null);
		}
		// Verlinkungen der Vaterelemente loesen
		if ($message->hasParent() && $this->hasMessageNum($message->getParentID())) {
			$this->getMessage($message->getParentID())->removeChild($message);
		}
		
		// Nachricht aus dem Thread haengen
		if ($this->hasThread($message->getThreadID())) {
			$this->getThread($message->getThreadID())->removeMessage($message);
		}
	}

	/**
	 * Fuege einen neuen Thread ein
	 **/
	private function addThread($thread) {
		$this->threads[$thread->getThreadID()] = $thread;
	}

	/**
	 * Fuegt eine Nachricht in die Queue ein und speichert sie
	 **/
	private function addQueueMessage($message) {
		$this->addMessage($message);
		$this->addQueue($message);
	}

	/**
	 * Loesche eine Nachricht aus Queue und Nachrichtenspeicher
	 **/
	private function removeQueueMessage($messageid) {
		$this->removeMessage($messageid);
		$this->removeQueue($messageid);
	}

	/**
	 * Gibt eine Liste der Message-IDs in der Queue zurueck
	 **/
	private function getQueue() {
		return array_keys($this->queue);
	}
	
	/**
	 * Prueft, ob die Nachricht in der Queue steht
	 **/
	private function hasQueued($messageid) {
		return isset($this->queue[$messageid]);
	}

	/**
	 * Fuegt eine Nachricht in die Queue ein
	 * Die eigentliche Message wird im regulaeren Nachrichtencontainer
	 * gespeichert.
	 **/
	private function addQueue($message) {
		$this->queue[$message->getMessageID()] = true;
	}

	/**
	 * Streiche die MessageID aus der Queue
	 **/
	private function removeQueue($messageid) {
		unset($this->queue[$messageid]);
	}
}

?>
