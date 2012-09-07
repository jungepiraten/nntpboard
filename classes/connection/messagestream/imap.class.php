<?php

// http://pear.php.net/package/Net_IMAP
include_once('Net/IMAP.php');

require_once(dirname(__FILE__)."/../messagestream.class.php");
require_once(dirname(__FILE__)."/rfc5322/header.class.php");
require_once(dirname(__FILE__)."/rfc5322/message.class.php");
require_once(dirname(__FILE__)."/../../exceptions/message.exception.php");

class IMAPConnection extends AbstractMessageStreamConnection {
	private $host;
	private $loginusername;
	private $loginpassword;
	private $folder;

	// MessageIDs
	private $messageids = null;

	private $imapclient;

	public function __construct(Host $host, $loginusername, $loginpassword, $folder) {
		parent::__construct();

		$this->host = $host;
		$this->loginusername = $loginusername;
		$this->loginpassword = $loginpassword;
		$this->folder = $folder;
	}

	public function getGroupID() {
		return __CLASS__ . ":" . $this->loginusername . "@" . $this->host . "/" . $this->folder;
	}

	public function open($auth) {
		// Verbindung oeffnen
		$this->imapclient = new Net_IMAP($this->host->getHost(), $this->host->getPort(), true, "UTF-8");

		// Authentifieren
		$ret = $this->imapclient->login($this->loginusername, $this->loginpassword);
		if (PEAR::isError($ret)) {
			throw new Exception($ret);
		}

		// Mailbox auswaehlen
		$this->imapclient->selectMailbox($this->folder);

		$this->refreshCache();
	}

	public function close() {
		$this->imapclient->disconnect();
	}

	// Interne Caches leeren, damit wir merken, dass sich etwas geaendert hat
	protected function refreshCache() {
		$this->messageids = null;
		$this->articles = null;
	}

	private function getArticleNr($msgid) {
		return $this->articles[$msgid];
	}

	public function getMessageIDs() {
		if ($this->messageids == null) {
			$messageCount = $this->imapclient->getNumberOfMessages();
			if ($messageCount == 0) {
				return array();
			}
			// Hole eine Uebersicht ueber alle verfuegbaren Posts
			$ret = $this->imapclient->cmdFetch("1:" . $messageCount, "envelope");
			if ($ret["RESPONSE"]["CODE"] != "OK") {
				throw new Exception($ret["RESPONSE"]["STR_CODE"]);
			} else {
				$articles = $ret["PARSED"];
				$this->messageids = array();
				foreach ($articles AS $article) {
					$this->articles[$article["EXT"]["ENVELOPE"]["MESSAGE_ID"]] = $article["NRO"];
					$this->messageids[$article["NRO"]] = $article["EXT"]["ENVELOPE"]["MESSAGE_ID"];
				}
			}
		}
		return $this->messageids;
	}

	public function getMessageCount() {
		return count($this->getMessageIDs());
	}
	public function hasMessage($msgid) {
		return in_array($msgid, $this->getMessageIDs());
	}
	public function getMessage($msgid) {
		// Lade die Nachricht und Parse sie
		if ($this->hasMessage($msgid)) {
			$article = $this->imapclient->getMessages($this->getArticleNr($msgid));
			if (PEAR::isError($article)) {
				throw new NotFoundMessageException($msgid, $this->folder);
			}
			$message = RFC5322Message::parsePlain(array_shift($article));
			$message = $message->getObject($this);

			return $message;
		}
		// Diese Nachricht gibt es offensichtlich nicht mehr ;)
		throw new NotFoundMessageException($msgid, $this->folder);
	}

	/**
	 * Schreibe eine Nachricht
	 **/
	public function postMessage($message) {
		return false;
	}
	public function postAcknowledge($ack, $message) {
		return false;
	}
	public function postCancel($cancel, $message) {
		return false;
	}
}

?>
