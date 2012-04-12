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

	private $cacheSentPosts;
	
	public function __construct($uplink, $cacheSentPosts = true) {
		parent::__construct();
		$this->uplink = $uplink;
		$this->cacheSentPosts = $cacheSentPosts;
	}

	public function getGroupID() {
		return $this->uplink->getGroupID();
	}

	protected function addMessageQueue($queueid, $message) {
		$queue = $this->getMessageQueue($queueid);
		$queue[] = $message;
		$this->setMessageQueue($queueid, $queue);
	}
	protected function delMessageQueue($queueid, $msgid) {
		$queue = $this->getMessageQueue($queueid);
		unset($queue[$msgid]);
		$this->setMessageQueue($queueid, $queue);
	}

	abstract protected function getMessageQueue($queueid);
	abstract protected function setMessageQueue($queueid, $queue);

	abstract protected function setGroupHash($hash);

	/**
	 * Poste eine Nachricht
	 * Dafuer nutzen wir eine Queue, die beim naechsten sync (cron.php) mittels
	 * sendMessages() abgearbeitet wird.
	 * Solange ist die Nachricht nur im Forum sichtbar.
	 **/
	private function handleMessage($message) {
		if ($message instanceof Message) {
			$this->getGroup()->addMessage($message);
		}
		if ($message instanceof Acknowledge) {
			$this->getGroup()->addMessage($message);
		}
		if ($message instanceof Cancel) {
			$this->getGroup()->removeMessage($message->getReference());
		}
		// Damit enforcen wir, dass die Daten in den Cache geschrieben werden und beim naechsten
		// update auch gepusht werden (s. postMessageCache())
		$this->setGroupHash(__CLASS__ . '$' . md5(microtime(true) . rand(100,999)));
	}
	private function hasLocalMessages() {
		return (substr($this->getGroupHash(),0,strlen(__CLASS__)) == __CLASS__);
	}

	public function postMessage($message) {
		if ($this->cacheSentPosts) {
			$this->addMessageQueue("message", $message);
			$this->handleMessage($message);
			return "y";
		}
		$this->uplink->open();
		// Die Berechtigungen prueft der Uplink selbst
		$resp = $this->uplink->postMessage($message);
		// Wenn der Uplink die Nachricht genommen hat, koennen wir sie direkt korrekt eintragen
		// Falls der Uplink moderiert ist, warten wir lieber, bis dieser die Nachricht rausrueckt
		if ($resp != "m") {
			$this->handleMessage($message);
		}
		$this->uplink->close();
		return $resp;
	}
	public function postAcknowledge($ack, $message) {
		if ($this->cacheSentPosts) {
			$this->addMessageQueue("acknowledge", array($ack, $message));
			$this->handleMessage($ack);
			return "y";
		}
		$this->uplink->open();
		// Die Berechtigungen prueft der Uplink selbst
		$resp = $this->uplink->postAcknowledge($ack, $message);
		// Wenn der Uplink die Nachricht genommen hat, koennen wir sie direkt korrekt eintragen
		// Falls der Uplink moderiert ist, warten wir lieber, bis dieser die Nachricht rausrueckt
		if ($resp != "m") {
			$this->handleMessage($ack);
		}
		$this->uplink->close();
		return $resp;
	}
	public function postCancel($cancel, $message) {
		if ($this->cacheSentPosts) {
			$this->addMessageQueue("cancel", array($cancel, $message));
			$this->handleMessage($cancel);
			return "y";
		}
		$this->uplink->open();
		// Die Berechtigungen prueft der Uplink selbst
		$resp = $this->uplink->postCancel($cancel, $message);
		// Wenn der Uplink die Nachricht genommen hat, koennen wir sie direkt korrekt eintragen
		// Falls der Uplink moderiert ist, warten wir lieber, bis dieser die Nachricht rausrueckt
		if ($resp != "m") {
			$this->handleMessage($cancel);
		}
		$this->uplink->close();
		return $resp;
	}

	public function getGroup() {
		return new StaticGroup($this->getGroupID(), $this->getGroupHash());
	}

	public function setGroup($group) {
		$cachegroup = $this->getGroup();

		if ($this->hasLocalMessages()) {
			$this->postMessageCache();
		}

		$uplinkmessageids = $group->getMessageIDs();
		$cachemessageids  = $cachegroup->getMessageIDs();
		
		// Liste mit neuen Nachrichten aufstellen
		$newmessages = array_diff($uplinkmessageids, $cachemessageids);
		foreach ($newmessages as $messageid) {
			$message = $group->getMessage($messageid);
			$cachegroup->addMessage($message);
		}

		// Veraltete Nachrichten ausstreichen (z.b. Cancel)
		$oldmessages = array_diff($cachemessageids, $uplinkmessageids);
		foreach ($oldmessages as $messageid) {
			$cachegroup->removeMessage($messageid);
		}

		$grouphash = $group->getGroupHash();
		$this->setGroupHash($grouphash);
		$cachegroup->setGroupHash($grouphash);
	}

	public function updateGroup() {
		$cachegroup = $this->getGroup();

		// Bei passendem Prefix pushen wir erstmal unsere Nachrichten
		if ($this->hasLocalMessages()) {
			$this->postMessageCache();
		}

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

		$grouphash = $this->uplink->getGroupHash();
		$this->setGroupHash($grouphash);
		$cachegroup->setGroupHash($grouphash);
	}

	private function postMessageCache() {
		foreach ($this->getMessageQueue("message") as $msgid => $message) {
			$this->uplink->postMessage($message);
			$this->delMessageQueue("message", $msgid);
		}
		foreach ($this->getMessageQueue("acknowledge") as $msgid => $msg) {
			list($ack, $message) = $msg;
			$this->uplink->postAcknowledge($ack, $message);
			$this->delMessageQueue("acknowledge", $msgid);
		}
		foreach ($this->getMessageQueue("cancel") as $msgid => $message) {
			list($cancel, $message) = $msg;
			$this->uplink->postCancel($cancel, $message);
			$this->delMessageQueue("cancel", $msgid);
		}
	}
	
	/**
	 * Hole neue Daten vom Uplink / Cache-Update
	 **/
	public function updateCache() {
		$this->uplink->open();
		// Gruppenhashes vergleichen (Schnellste Moeglichkeit)
		if ($this->uplink->getGroupHash() != $this->getGroupHash()) {
			/* Wenn unser Uplink uns die Nachrichten auch direkt geben kann,
			 * muessen wir nicht erst die komplette Gruppe laden */
			if ($this->uplink instanceof MessageStream) {
				$this->updateGroup();
			} else {
				$this->setGroup($this->uplink->getGroup());
			}
		}
		$this->uplink->close();
	}
}

?>
