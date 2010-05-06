<?php

require_once(dirname(__FILE__)."/../connection.class.php");
/* Die Klassen müssen vor dem unserialize eingebunden sein, da PHP sonst
 * incomplete Objekte erstellt.
 * vgl. http://mrfoo.de/archiv/120-The-script-tried-to-execute-a-method-or-access-a-property-of-an-incomplete-object.html
 **/
require_once(dirname(__FILE__)."/../address.class.php");
require_once(dirname(__FILE__)."/../thread.class.php");
require_once(dirname(__FILE__)."/../message.class.php");
require_once(dirname(__FILE__)."/../exceptions/group.exception.php");
require_once(dirname(__FILE__)."/../exceptions/thread.exception.php");
require_once(dirname(__FILE__)."/../exceptions/message.exception.php");
require_once(dirname(__FILE__)."/../exceptions/datadir.exception.php");

class CacheConnection extends AbstractConnection {
	private $group;
	private $auth;
	private $cache;
	/**
	 * $uplink - Die Verbindung, mit der Nachrichten synchronisiert werden
	 **/
	private $uplink;

	// MessageID => Message
	private $messages = array();
	// MessageID => ThreadID
	private $threadids = array();
	// ThreadID => Thread
	private $threads = array();
	// MessageID => true
	private $queue = array();
	
	public function __construct($group, $auth, $cache, $uplink = null) {
		parent::__construct();
		
		$this->group = $group;
		$this->auth = $auth;
		$this->cache = $cache;
		$this->uplink = $uplink;
	}
	
	public function open() {
		$this->cache->open();
	}

	public function close() {
		$this->cache->close();
	}


	public function getMessageIDs() {
		return $this->cache->getMessageIDs();
	}

	public function getMessageCount() {
		return $this->cache->getMessageCount();
	}

	public function hasMessage($messageid) {
		return $this->cache->hasMessage($messageid);
	}

	public function getMessage($messageid) {
		return $this->cache->getMessage($messageid);
	}


	public function getThreadIDs() {
		return $this->cache->getThreadIDs();
	}

	public function getThreadCount() {
		return $this->cache->getThreadCount();
	}

	public function hasThread($messageid) {
		return $this->cache->hasThread($messageid);
	}

	public function getThread($messageid) {
		return $this->cache->getThread($messageid);
	}


	protected function getLastThread() {
		return array_slice($this->getThreads(), 0, 1);
	}

	/**
	 * Poste eine Nachricht
	 * Dafuer nutzen wir eine Queue, die beim naechsten sync (cron.php) mittels
	 * sendMessages() abgearbeitet wird.
	 * Solange ist die Nachricht nur im Forum sichtbar.
	 **/
	public function post($message) {
		// Falls vorhanden, posten wir das erstmal woanders (evtl kommen dabei ja Exceptions)
		if ($this->uplink !== null) {
			$this->uplink->open();
			$this->uplink->post($message);
			$this->uplink->close();
		}
		// Berechtigungscheck
		if (!$this->group->mayPost($this->auth)) {
			throw new PostingNotAllowedException();
		}
		// Moderierte Nachrichten kommen via NNTP rein (direkt ueber addMessage)
		if ($this->group->isModerated()) {
			return;
		}
		// Markiere die Nachricht nur in der Queue, falls der Uplink sie nicht schon hat.
		if ($this->uplink !== null) {
			$this->addMessage($message);
		} else {
			$this->addQueueMessage($message);
		}
		$this->cache->sort();
	}

	/**
	 * Poste Queue-Nachrichten auf den Uplink
	 **/
	public function sendMessages() {
		if ($this->uplink == null) {
			return;
		}
		$this->uplink->open();
		foreach ($this->getQueue() AS $messageid) {
			// Nachricht laden
			$message = $this->getMessage($messageid);
			// Nachricht posten und hier Loeschen
			// Falls NNTP sie annimmmt, kommt sie mit loadMessages() wieder rein
			$this->uplink->post($message);
			$this->removeQueueMessage($message);
		}
		$this->uplink->close();
	}
	
	/**
	 * Hole neue Daten vom Uplink
	 **/
	public function loadMessages() {
		if ($this->uplink == null) {
			return;
		}
		$this->uplink->open();
		// Wenn die hoechste ArtikelNum sich nicht veraendert hat, hat sich gar nix getan (spart sortieren)
		if ($this->uplink->getMessageCount() <= 0
		 || $this->uplink->getMessageCount() == $this->getMessageCount()) {
			$this->uplink->close();
			return;
		}
		// Liste mit neuen Nachrichten aufstellen
		$newmessages = array_diff($this->uplink->getMessageIDs(), $this->getMessageIDs());

		foreach ($newmessages as $messageid) {
			$message = $this->uplink->getMessage($messageid);
			$this->addMessage($message);
		}
		$this->uplink->close();
		$this->cache->sort();
	}
	
	/**
	 * Queue-Verwaltung
	 **/
	private function getQueue() {
		return $this->cache->getQueue();
	}

	private function addQueueMessage($message) {
		$this->addMessage($message);
		$this->cache->addToQueue($message);
	}

	private function removeQueueMessage($messageid) {
		$this->removeMessage($messageid);
		$this->cache->removeFromQueue($messageid);
	}
	
	/**
	 * Fuege eine Nachricht ein und referenziere sie
	 **/
	private function addMessage($message) {
		// Unterpost verlinkenuplink
		if ($message->hasParent() && $this->hasMessage($message->getParentID())) {
			$thread = $this->getThread($message->getParentID());
			$this->getMessage($message->getParentID())->addChild($message);
		} else {
			$thread = new Thread($message);
		}

		// Nachricht zum Thread hinzufuegen
		$thread->addMessage($message);

		$this->cache->addMessage($message, $thread);

		/**
		 * Wenn wir die Nachricht vom NNTP bekommen haben, koennen wir ihn aus der Queue streichen
		 * Wenn diese Nachricht aus der Queue kommt, wird sie gleich in die Queue eingetragen
		 * => Einfach aus der Queue streichen, falls noetig wirds wieder eingetragen
		 */
		if ($this->cache->hasQueued($message->getMessageID())) {
			$this->cache->removeQueue($message->getMessageID());
		}
	}

	/**
	 * Loesche die Nachricht mit allen Referenzen
	 **/
	private function removeMessage($messageid) {
		$message = $this->getMessage($messageid);
		$this->cache->removeMessage($messageid);

		// Unterelemente auf das Vaterelement schieben
		foreach ($message->getChilds() AS $messageid) {
			$this->getMessage($messageid)->setParentID($message->hasParentID() ? $message->getParentID() : null);
		}
		// Verlinkungen der Vaterelemente loesen
		if ($message->hasParent() && $this->hasMessageNum($message->getParentID())) {
			$this->getMessage($message->getParentID())->removeChild($message);
		}
		
		// Nachricht aus dem Thread haengen
		if ($this->hasThread($message->getMessageID())) {
			$this->getThread($message->getMessageID())->removeMessage($message);
		}
	}
}

?>
