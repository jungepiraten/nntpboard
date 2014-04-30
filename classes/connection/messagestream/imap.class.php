<?php

// http://pear.php.net/package/Net_IMAP
include_once('Net/IMAP.php');
// http://pear.php.net/package/Net_SMTP
include_once('Net/SMTP.php');

require_once(dirname(__FILE__)."/rfc5322.class.php");
require_once(dirname(__FILE__)."/../../exceptions/message.exception.php");

class IMAPConnection extends AbstractRFC5322Connection {
	private $host;
	private $loginusername;
	private $loginpassword;
	private $folder;
	private $writer;

	// MessageIDs
	private $messageids = null;

	private $imapclient;

	public function __construct(Host $host, $loginusername, $loginpassword, $folder, $boardindexer, $writer = false) {
		parent::__construct($boardindexer);

		$this->host = $host;
		$this->loginusername = $loginusername;
		$this->loginpassword = $loginpassword;
		$this->folder = $folder;
		$this->writer = $writer;
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
		if (PEAR::isError($this->imapclient->selectMailbox($this->folder))) {
			$this->messageids = array();
			$this->articles = array();
		} else {
			$this->messageids = null;
			$this->articles = null;
		}
	}

	public function close() {
		$this->imapclient->disconnect();
	}

	private function getArticleNr($msgid) {
		return $this->articles[$msgid];
	}

	public function getMessageIDs() {
		if ($this->messageids == null) {
			$messageCount = $this->getMessageCount();
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
		if (isset($this->messageids)) {
			return count($this->messageids);
		}
		return $this->imapclient->getNumberOfMessages();
	}

	public function hasMessage($msgid) {
		return in_array($msgid, $this->getMessageIDs());
	}

	protected function getRFC5322Message($msgid) {
		// Lade die Nachricht und Parse sie
		if ($this->hasMessage($msgid)) {
			$article = $this->imapclient->getMessages($this->getArticleNr($msgid));
			if (PEAR::isError($article)) {
				throw new NotFoundMessageException($msgid, $this->folder);
			}
			return RFC5322Message::parsePlain(array_shift($article));
		}
		// Diese Nachricht gibt es offensichtlich nicht mehr ;)
		throw new NotFoundMessageException($msgid, $this->folder);
	}

	/**
	 * Schreibe eine Nachricht
	 **/

	public function post($message) {
		if ($this->writer != false) {
			return $this->writer->post($message);
		}
		return false;
	}
}

?>
