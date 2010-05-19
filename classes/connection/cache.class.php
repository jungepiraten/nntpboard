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
	private $readonly;

	/**
	 * $cache - Der Cache, der die Nachrichten speichert
	 **/
	private $cache;

	/**
	 * $uplink - Die Verbindung, mit der Nachrichten synchronisiert werden
	 **/
	private $uplink;
	
	public function __construct($cache, $uplink = null, $readonly = false) {
		parent::__construct();
		
		$this->cache = $cache;
		$this->uplink = $uplink;
		$this->readonly = $readonly;
	}
	
	public function open() {
		$this->cache->open();
	}

	public function close() {
		$this->cache->close();
	}

	public function getMessageCount() {
		return $this->cache->getMessageCount();
	}

	public function getMessageIDs() {
		return $this->cache->getMessageIDs();
	}

	public function getGroup() {
		$group = parent::getGroup();
		$messages = $this->cache->getMessageIDs();
		foreach ($messages as $messageid) {
			$group->addMessage($this->cache->getMessage($messageid));
		}
		return $group;
	}

	protected function mayRead() {
		return true;
	}

	protected function mayPost() {
		// TODO uplink fragen
		return ! $this->readonly;
	}

	protected function isModerated() {
		// Warum sollte ein Cache Moderiert sein?
		// falls wir sowas mal brauchen, gehört es in den Uplink
		return false;
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
			// Die Berechtigungen prueft der Uplink selbst
			$this->uplink->post($message);
			// Wenn der Uplink die Nachricht genommen hat, koennen wir sie direkt korrekt eintragen
			// Falls der Uplink moderiert ist, warten wir lieber, bis dieser die Nachricht rausrueckt
			if (!$this->uplink->isModerated()) {
				$this->addMessage($message);
			}
			$this->uplink->close();
		} else {
			// Berechtigungscheck
			if ($this->isReadOnly()) {
				throw new PostingNotAllowedException($this->group);
			}
			$this->addMessage($message);
		}
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
		$group = $this->uplink->getGroup();

		// Liste mit neuen Nachrichten aufstellen
		$newmessages = array_diff($this->uplink->getMessageIDs(), $this->getMessageIDs());
		foreach ($newmessages as $messageid) {
			$message = $this->uplink->getMessage($messageid);
			$this->addMessage($message);
		}

		// Veraltete Nachrichten ausstreichen (z.b. Cancel)
		$oldmessages = array_diff($this->getMessageIDs(), $this->uplink->getMessageIDs());
		foreach ($oldmessages as $messageid) {
			$this->removeMessage($messageid);
		}

		$this->uplink->close();
	}
	
	/**
	 * Fuege eine Nachricht ein und referenziere sie
	 **/
	private function addMessage($message) {
		$this->cache->addMessage($message);

		/**
		 * Wenn wir die Nachricht vom Uplink bekommen haben, koennen wir ihn aus der Queue streichen
		 * Wenn diese Nachricht aus der Queue kommt, wird sie gleich in die Queue eingetragen
		 * => Einfach aus der Queue streichen, falls noetig wirds wieder eingetragen
		 */
		#if ($this->cache->hasQueued($message->getMessageID())) {
		#	$this->cache->removeQueue($message->getMessageID());
		#}
	}

	/**
	 * Loesche die Nachricht mit allen Referenzen
	 **/
	private function removeMessage($messageid) {
		$this->cache->removeMessage($messageid);
	}
}

?>
