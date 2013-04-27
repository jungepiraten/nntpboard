<?php

require_once(dirname(__FILE__)."/../connection.class.php");
require_once(dirname(__FILE__)."/../boardindexer.class.php");
require_once(dirname(__FILE__)."/../group/static.class.php");
/* Die Klassen müssen vor dem unserialize eingebunden sein, da PHP sonst
 * incomplete Objekte erstellt.
 * vgl. http://mrfoo.de/archiv/120-The-script-tried-to-execute-a-method-or-access-a-property-of-an-incomplete-object.html
 **/
require_once(dirname(__FILE__)."/../address.class.php");
require_once(dirname(__FILE__)."/../thread.class.php");
require_once(dirname(__FILE__)."/../message.class.php");
require_once(dirname(__FILE__)."/../acknowledge.class.php");
require_once(dirname(__FILE__)."/../cancel.class.php");
require_once(dirname(__FILE__)."/../exceptions/group.exception.php");
require_once(dirname(__FILE__)."/../exceptions/thread.exception.php");
require_once(dirname(__FILE__)."/../exceptions/message.exception.php");
require_once(dirname(__FILE__)."/../exceptions/datadir.exception.php");

abstract class AbstractCacheConnection extends AbstractConnection {
	/**
	 * $uplink - Die Verbindung, mit der Nachrichten synchronisiert werden
	 **/
	private $uplink;

	private $boardindexer;

	private $cacheSentPosts;

	public function __construct($uplink, $cacheSentPosts = true) {
		parent::__construct($uplink->getBoardIndexer());
		$this->uplink = $uplink;
		$this->cacheSentPosts = $cacheSentPosts;
	}

	public function open($auth) {
		$this->auth = $auth;
	}

	public function close() {
		$this->auth = null;
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

	/** public for MixedItemCacheConnection **/
	abstract public function getMessageQueue($queueid);
	abstract public function setMessageQueue($queueid, $queue);

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
			$this->addMessageQueue("message", array($this->auth, $message));
			$this->handleMessage($message);
			return "y";
		}
		$resp = $this->postCacheMessage($this->auth, $message);
		// Wenn der Uplink die Nachricht genommen hat, koennen wir sie direkt korrekt eintragen
		if ($resp == "y") {
			$this->handleMessage($message);
		}
		return $resp;
	}
	public function postAcknowledge($ack, $message) {
		if ($this->cacheSentPosts) {
			$this->addMessageQueue("acknowledge", array($this->auth, $ack, $message));
			$this->handleMessage($ack);
			return "y";
		}
		$resp = $this->postCacheAcknowledge($this->auth, $ack, $message);
		// Wenn der Uplink die Nachricht genommen hat, koennen wir sie direkt korrekt eintragen
		if ($resp == "y") {
			$this->handleMessage($cancel);
		}
		return $resp;
	}
	public function postCancel($cancel, $message) {
		if ($this->cacheSentPosts) {
			$this->addMessageQueue("cancel", array($this->auth, $cancel, $message));
			$this->handleMessage($cancel);
			return "y";
		}
		$resp = $this->postCacheCancel($this->auth, $cancel, $message);
		// Wenn der Uplink die Nachricht genommen hat, koennen wir sie direkt korrekt eintragen
		if ($resp == "y") {
			$this->handleMessage($cancel);
		}
		return $resp;
	}

	private function postCacheMessage($auth, $message) {
		// Die Berechtigungen prueft der Uplink selbst
		$this->uplink->open($auth);
		$resp = $this->uplink->postMessage($message);
		$this->uplink->close();
		return $resp;
	}
	private function postCacheAcknowledge($auth, $ack, $message) {
		// Die Berechtigungen prueft der Uplink selbst
		$this->uplink->open($auth);
		$resp = $this->uplink->postAcknowledge($ack, $message);
		$this->uplink->close();
		return $resp;
	}
	private function postCacheCancel($auth, $cancel, $message) {
		// Die Berechtigungen prueft der Uplink selbst
		$this->uplink->open($auth);
		$resp = $this->uplink->postCancel($cancel, $message);
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

	private function postCache() {
		foreach ($this->getMessageQueue("message") as $msgid => $msg) {
			list($auth, $message) = $msg;
			$this->postCacheMessage($auth, $message);
			$this->delMessageQueue("message", $msgid);
		}
		foreach ($this->getMessageQueue("acknowledge") as $msgid => $msg) {
			list($auth, $ack, $message) = $msg;
			$this->postCacheAcknowledge($auth, $ack, $message);
			$this->delMessageQueue("acknowledge", $msgid);
		}
		foreach ($this->getMessageQueue("cancel") as $msgid => $msg) {
			list($auth, $cancel, $message) = $msg;
			$this->postCacheCancel($auth, $cancel, $message);
			$this->delMessageQueue("cancel", $msgid);
		}
	}

	/**
	 * Hole neue Daten vom Uplink / Cache-Update
	 **/
	public function updateCache() {
		if ($this->hasLocalMessages()) {
			$this->postCache();
		}

		$this->uplink->open($this->auth);
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
