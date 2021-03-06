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
		$this->imapclient = new Net_IMAP($this->host->getHost(), $this->host->getPort(), $this->host->useStartTLS(), "UTF-8");

		// Authentifieren
		$ret = $this->imapclient->login($this->loginusername, $this->loginpassword);
		if (PEAR::isError($ret)) {
			throw new Exception($ret);
		}

		// Mailbox auswaehlen
		$ret = $this->imapclient->selectMailbox($this->folder);
		if (PEAR::isError($ret)) {
			throw new Exception($ret);
		}

		// Mark connection as unused (no data received so far)
		$this->messageids = null;
		$this->articles = null;
	}

	public function close() {
		// Expunge on disconnect
		$this->imapclient->disconnect(true);
	}

	private function getArticleNr($msgid) {
		// Lade zuordnung falls nicht bereits geschehen
		if ($this->articles == null) {
			$this->getMessageIDs();
		}
		return $this->articles[$msgid];
	}

	public function getMessageIDs() {
		if ($this->messageids == null) {
			$messageCount = $this->getMessageCount();
			if ($messageCount == 0) {
				return array();
			}
			// Hole eine Uebersicht ueber alle verfuegbaren Posts
			$ret = $this->imapclient->cmdFetch("1:" . $messageCount, "BODY[HEADER.FIELDS (MESSAGE-ID)]");
			if ($ret["RESPONSE"]["CODE"] != "OK") {
				throw new Exception($ret["RESPONSE"]["STR_CODE"]);
			} else {
				$articles = $ret["PARSED"];
				$this->messageids = array();
				foreach ($articles AS $article) {
					$msgids = explode(":", $article["EXT"]["BODY[HEADER.FIELDS (MESSAGE-ID)]"]["CONTENT"], 2);
					if (count($msgids) != 2) {
						$msgid = "<envelope-" . md5(serialize($article["EXT"]["ENVELOPE"])) . "@generated.local>";
					} else {
						$msgid = trim($msgids[1]);
					}
					$this->articles[$msgid] = $article["NRO"];
					$this->messageids[$article["NRO"]] = $msgid;
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

	public function postCancel($cancel, $message) {
		// Nachricht wird zum löschen markiert. Löschen erst bei Disconnect (sonst muss die Nummerierung geändert werden)
		$this->imapclient->deleteMessages($this->getArticleNr($message->getMessageID()));
	}

	public function post($message) {
		if ($this->writer != false) {
			return $this->writer->post($message);
		}
		return false;
	}
}

?>
