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

abstract class AbstractCacheConnection extends AbstractConnection {
	/**
	 * $uplink - Die Verbindung, mit der Nachrichten synchronisiert werden
	 **/
	private $uplink;
	
	public function __construct($uplink = null) {
		parent::__construct();
		
		$this->uplink = $uplink;
	}

	public function getGroupID() {
		return $this->uplink->getGroupID();
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
			$resp = $this->uplink->post($message);
			// Wenn der Uplink die Nachricht genommen hat, koennen wir sie direkt korrekt eintragen
			// Falls der Uplink moderiert ist, warten wir lieber, bis dieser die Nachricht rausrueckt
			if ($resp != "m") {
				$this->getGroup()->addMessage($message);
			}
			$this->uplink->close();
		} else {
			$this->getGroup()->addMessage($message);
		}
	}
	
	/**
	 * Hole neue Daten vom Uplink / Cache-Update
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
		$this->setGroup($this->uplink->getGroup());
		$this->uplink->close();

	}

	/**
	 * getGroup() - wird von anderen CacheConnections ueberschrieben und hier
	 * nur rudimentaer implementiert
	 **/
	public function getGroup() {
		$group = parent::getGroup();
		// TODO neue nachrichten vom Uplink einfuegen
		return $group;
	}

	public function setGroup($group) {
		$cachegroup = $this->getGroup();
		
		// Liste mit neuen Nachrichten aufstellen
		$newmessages = array_diff($group->getMessageIDs(), $cachegroup->getMessageIDs());
		foreach ($newmessages as $messageid) {
			$message = $group->getMessage($messageid);
			$cachegroup->addMessage($message);
		}

		// Veraltete Nachrichten ausstreichen (z.b. Cancel)
		$oldmessages = array_diff($cachegroup->getMessageIDs(), $group->getMessageIDs());
		foreach ($oldmessages as $messageid) {
			$cachegroup->removeMessage($messageid);
		}
	}
}

?>
