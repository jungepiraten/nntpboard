<?php

require_once(dirname(__FILE__)."/../connection.class.php");
require_once(dirname(__FILE__)."/../boardindexer.class.php");
require_once(dirname(__FILE__)."/../group/dynamic.class.php");

interface CacheConnection {
	public function getMessageQueue($queueid);
	public function setMessageQueue($queueid, $queue);
	public function loadMessageIDs();
	public function saveMessageIDs($messageids);
	public function loadMessageThreads();
	public function saveMessageThreads($messagethreads);
	public function loadThreadsLastPost();
	public function saveThreadsLastPost($messageids);
	public function loadMessage($messageid);
	public function saveMessage($messageid, $message);
	public function removeMessage($messageid);
	public function loadThread($threadid);
	public function saveThread($threadid, $thread);
	public function removeThread($threadid);
	public function loadAcknowledges($messageid);
	public function saveAcknowledges($messageid, $acks);
	public function loadGroupHash();
	public function saveGroupHash($hash);
}

abstract class AbstractCacheConnection extends AbstractConnection implements CacheConnection {
	/**
	 * $uplink - Die Verbindung, mit der Nachrichten synchronisiert werden
	 **/
	private $uplink;

	private $group;

	/* Anhand von $grouphash != $oldgrouphash koennen wir feststellen, ob wir
	 * komplex speichern muessen oder einfach fertig sind (siehe hasChanged) */
	private $grouphash;
	private $oldgrouphash;

	public function __construct(Messagestream $uplink) {
		parent::__construct($uplink->getBoardIndexer());
		$this->uplink = $uplink;
	}

	public function open($auth) {
		$this->auth = $auth;
		$this->oldgrouphash = $this->grouphash = $this->loadGroupHash();
	}

	public function close() {
		$this->auth = null;
		if ($this->hasChanged()) {
			$this->saveGroupHash($this->grouphash);
			if ($this->group !== null) {
				$this->saveMessageIDs($this->group->getMessageIDs());
				$this->saveMessageThreads($this->group->getMessageThreads());
				$this->saveThreadsLastPost($this->group->getThreadsLastPost());
				foreach ($this->group->getNewMessagesIDs() as $messageid) {
					$this->saveMessage($messageid, $this->group->getMessage($messageid));
				}
				foreach ($this->group->getNewThreadIDs() as $threadid) {
					$this->saveThread($threadid, $this->group->getThread($threadid));
				}
				foreach ($this->group->getAcknowledgeIDs() as $messageid) {
					$this->saveAcknowledges($messageid, $this->group->getAcknowledgeMessageIDs($messageid));
				}
			}
		}
	}

	public function getGroupID() {
		return $this->uplink->getGroupID();
	}

	public function getGroupHash() {
		return $this->grouphash;
	}
	// gets called from DynamicGroup
	public function setGroupHash($hash) {
		$this->grouphash = $hash;
	}
	private function hasChanged() {
		return $this->grouphash !== $this->oldgrouphash;
	}

	public function getGroup() {
		if ($this->group === null) {
			$this->group = new DynamicGroup($this);
		}
		return $this->group;
	}

	private function addMessageQueue($queueid, $message) {
		$queue = $this->getMessageQueue($queueid);
		$queue[] = $message;
		$this->setMessageQueue($queueid, $queue);
	}
	private function delMessageQueue($queueid, $msgid) {
		$queue = $this->getMessageQueue($queueid);
		unset($queue[$msgid]);
		$this->setMessageQueue($queueid, $queue);
	}

	/**
	 * Poste eine Nachricht
	 * Dafuer nutzen wir eine Queue, die beim naechsten sync (cron.php) mittels
	 * postLocalMessages() abgearbeitet wird.
	 * Solange ist die Nachricht nur im Forum sichtbar.
	 **/
	private function handleLocalMessage($message) {
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

	public function postMessage($message) {
		$this->addMessageQueue("message", array($this->auth, $message));
		$this->handleLocalMessage($message);
		return true;
	}
	public function postAcknowledge($ack, $message) {
		$this->addMessageQueue("acknowledge", array($this->auth, $ack, $message));
		$this->handleLocalMessage($ack);
		return true;
	}
	public function postCancel($cancel, $message) {
		$this->addMessageQueue("cancel", array($this->auth, $cancel, $message));
		$this->handleLocalMessage($cancel);
		return true;
	}

	private function updateLocal($maxAddCount = 0, $maxDeleteCount = 0) {
		$cachegroup = $this->getGroup();

		try {
			$grouphash = $this->uplink->getGroupHash();
			$uplinkmessageids = $this->uplink->getMessageIDs();
			$cachemessageids  = $cachegroup->getMessageIDs();

			// Liste mit neuen Nachrichten aufstellen
			$newmessages = array_diff($uplinkmessageids, $cachemessageids);
			// Schutz vor Überlastung - verarbeite restliche Nachrichten später
			if ($maxAddCount > 0 and count($newmessages) > $maxAddCount) {
				$newmessages = array_slice($newmessages, 0, $maxAddCount);
				// Starte cron in nächstem Durchlauf erneut
				$grouphash = __CLASS__ . '$' . md5(microtime(true) . rand(100,999));
			}
			foreach ($newmessages as $messageid) {
				$message = $this->uplink->getMessage($messageid);
				$cachegroup->addMessage($message);
			}

			// Veraltete Nachrichten ausstreichen (z.b. Cancel)
			$oldmessages = array_diff($cachemessageids, $uplinkmessageids);
			// Schutz vor Überlastung - verarbeite restliche Nachrichten später
			if ($maxDeleteCount > 0 and count($oldmessages) > $maxDeleteCount) {
				$oldmessages = array_slice($oldmessages, 0, $maxDeleteCount);
				// Starte cron in nächstem Durchlauf erneut
				$grouphash = __CLASS__ . '$' . md5(microtime(true) . rand(100,999));
			}
			foreach ($oldmessages as $messageid) {
				$cachegroup->removeMessage($messageid);
			}

			$this->setGroupHash($grouphash);
			$cachegroup->setGroupHash($grouphash);
		} catch (Exception $e) {
			// Not throw an exception here: close() needs to be called
			print($this->getGroupID() . ": " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n");

			// Damit enforcen wir, dass die Daten in den Cache geschrieben werden und beim naechsten
			// update auch gepusht werden (s. postMessageCache())
			$this->setGroupHash(__CLASS__ . '$' . md5(microtime(true) . rand(100,999)));
		}
	}

	private function postLocalMessages() {
		foreach ($this->getMessageQueue("message") as $msgid => $msg) {
			list($auth, $message) = $msg;
			// Die Berechtigungen prueft der Uplink selbst
			$this->uplink->open($auth);
			$this->uplink->postMessage($message);
			$this->uplink->close();
			$this->delMessageQueue("message", $msgid);
		}
		foreach ($this->getMessageQueue("acknowledge") as $msgid => $msg) {
			list($auth, $ack, $message) = $msg;
			// Die Berechtigungen prueft der Uplink selbst
			$this->uplink->open($auth);
			$this->uplink->postAcknowledge($ack, $message);
			$this->uplink->close();
			$this->delMessageQueue("acknowledge", $msgid);
		}
		foreach ($this->getMessageQueue("cancel") as $msgid => $msg) {
			list($auth, $cancel, $message) = $msg;
			// Die Berechtigungen prueft der Uplink selbst
			$this->uplink->open($auth);
			$this->uplink->postAcknowledge($cancel, $message);
			$this->uplink->close();
			$this->delMessageQueue("cancel", $msgid);
		}
	}

	/**
	 * Hole neue Daten vom Uplink / Cache-Update. Einsprungspunkt für bin/cron.php
	 **/
	public function updateCache($maxAddCount = 0, $maxDeleteCount = 0) {
		$this->postLocalMessages();

		$this->uplink->open($this->auth);
		if ($this->uplink->getGroupHash() != $this->getGroupHash()) {
			$this->updateLocal($maxAddCount, $maxDeleteCount);
		}
		$this->uplink->close();
	}
}

?>
