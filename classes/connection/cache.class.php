<?php

require_once(dirname(__FILE__)."/../connection.class.php");
require_once(dirname(__FILE__)."/../group/static.class.php");
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
	
	public function __construct($uplink) {
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
	public function postMessage($message) {
		$this->uplink->open();
		// Die Berechtigungen prueft der Uplink selbst
		$resp = $this->uplink->postMessage($message);
		// Wenn der Uplink die Nachricht genommen hat, koennen wir sie direkt korrekt eintragen
		// Falls der Uplink moderiert ist, warten wir lieber, bis dieser die Nachricht rausrueckt
		if ($resp != "m") {
			$this->getGroup()->addMessage($message);
		}
		$this->uplink->close();
		return $resp;
	}
	public function postAcknowledge($ack, $message) {
		$this->uplink->open();
		// Die Berechtigungen prueft der Uplink selbst
		$resp = $this->uplink->postAcknowledge($ack, $message);
		// Wenn der Uplink die Nachricht genommen hat, koennen wir sie direkt korrekt eintragen
		// Falls der Uplink moderiert ist, warten wir lieber, bis dieser die Nachricht rausrueckt
		if ($resp != "m") {
			$this->getGroup()->addAcknowledge($ack);
		}
		$this->uplink->close();
		return $resp;
	}
	public function postCancel($cancel, $message) {
		$this->uplink->open();
		// Die Berechtigungen prueft der Uplink selbst
		$resp = $this->uplink->postCancel($cancel, $message);
		// Wenn der Uplink die Nachricht genommen hat, koennen wir sie direkt korrekt eintragen
		// Falls der Uplink moderiert ist, warten wir lieber, bis dieser die Nachricht rausrueckt
		if ($resp != "m") {
			$this->getGroup()->removeMessage($cancel->getReference());
		}
		$this->uplink->close();
		return $resp;
	}

	public function getGroup() {
		return new StaticGroup($this->getGroupID(), $this->getGroupHash());
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

		$cachegroup->setGroupHash($group->getGroupHash());
	}

	public function updateGroup() {
		$cachegroup = $this->getGroup();

		$uplinkmessageids = $this->uplink->getMessageIDs();
		$cachemessageids  = $cachegroup->getMessageIDs();

		// Liste mit neuen Nachrichten aufstellen
		$newmessages = array_diff($uplinkmessageids, $cachemessageids);
		foreach ($newmessages as $messageid) {
			$message = $this->uplink->getMessage($messageid);
			$cachegroup->addMessage($message);
		}

		// Veraltete Nachrichten ausstreichen (z.b. Cancel)
		$oldmessages = array_diff($cachemessageids, $uplinkmessageids);
		foreach ($oldmessages as $messageid) {
			$cachegroup->removeMessage($messageid);
		}

		$cachegroup->setGroupHash($this->uplink->getGroupHash());
	}

	abstract protected function setGroupHash($hash);
	
	/**
	 * Hole neue Daten vom Uplink / Cache-Update
	 **/
	public function updateCache() {
		$this->uplink->open();
		// Gruppenhashes vergleichen
		if (false && $this->uplink->getGroupHash() == $this->getGroupHash()) {
			$this->uplink->close();
			return;
		}
		/* Wenn unser Uplink uns die Nachrichten auch direkt geben kann,
		 * muessen wir nicht erst die komplette Gruppe laden */
		if ($this->uplink instanceof MessageStream) {
			$this->updateGroup();
		} else {
			$this->setGroup($this->uplink->getGroup());
		}
		$this->uplink->close();
	}
}

?>
