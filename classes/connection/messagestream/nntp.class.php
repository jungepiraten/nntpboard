<?php

// http://pear.php.net/package/Net_NNTP
require_once("Net/NNTP/Client.php");

require_once(dirname(__FILE__)."/../messagestream.class.php");
require_once(dirname(__FILE__)."/nntp/header.class.php");
require_once(dirname(__FILE__)."/nntp/message.class.php");
require_once(dirname(__FILE__)."/../../exceptions/group.exception.php");

class NNTPConnection extends AbstractMessageStreamConnection {
	private $host;
	private $group;
	private $username;
	private $password;

	// Erste ArtNr und Letzte ArtNr
	private $firstartnr;
	private $lastartnr;
	// MessageID => ArtikelNum
	private $messageids = null;
	// y (read-write), n (read-only) oder m (moderiert)
	private $mode = null;

	private $nntpclient;
	
	public function __construct(Host $host, $group, Auth $auth = null) {
		parent::__construct();

		$this->host = $host;
		$this->group = $group;

		if (isset($auth)) {
			$this->username = $auth->getNNTPUsername();
			$this->password = $auth->getNNTPPassword();
		}

		// Verbindung initialisieren
		$this->nntpclient = new Net_NNTP_Client;
	}
	
	public function getGroupID() {
		return __CLASS__ . ":" . $this->group . "@" . $this->host;
	}
	
	public function open() {
		// Verbindung oeffnen
		$ret = $this->nntpclient->connect($this->host->getHost(), false, $this->host->getPort());
		if (PEAR::isError($ret)) {
			throw new Exception($ret);
		}
		// Zwar unschoen, aber die PEAR-DB laesst keine andere Moeglichkeit
		$this->nntpclient->cmdModeReader();
		
		// ggf. Authentifieren
		if (isset($this->username)) {
			$ret = $this->nntpclient->authenticate($this->username, $this->password);
			if (PEAR::isError($ret)) {
				throw new Exception($ret);
			}
		}

		// Gruppenmodus laden
		$ret = $this->nntpclient->getGroups($this->group, true);
		if (PEAR::isError($ret)) {
			throw new Exception($ret);
		}
		$this->mode = $ret[$this->group]["posting"];
		
		// Waehle die passende Gruppe aus
		// Hole Zuordnung ArtNr <=> MessageID
		$ret = $this->nntpclient->selectGroup($this->group, true);
		if (PEAR::isError($ret)) {
			throw new Exception($ret);
		}
		$this->firstartnr = $ret["first"];
		$this->lastartnr = $ret["last"];
	}
	
	public function close() {
		$this->nntpclient->disconnect();
	}

	public function getMessageIDArtNrs() {
		if ($this->messageids == null) {
			// Hole eine Uebersicht ueber alle verfuegbaren Posts
			$articles = $this->nntpclient->getOverview($this->firstartnr . "-" . $this->lastartnr);
			if (PEAR::isError($articles)) {
				throw new Exception($articles);
			} else {
				$this->messageids = array();
				foreach ($articles AS $article) {
					$this->messageids[$article["Message-ID"]] = $article["Number"];
				}
			}			
		}
		return $this->messageids;
	}


	public function getMessageIDs() {
		return array_keys($this->getMessageIDArtNrs());
	}
	public function getMessageCount() {
		return count($this->getMessageIDArtNrs());
	}
	public function hasMessage($msgid) {
		$messageids = $this->getMessageIDArtNrs();
		return isset($messageids[$msgid]);
	}
	public function getMessage($msgid) {
		// Frage zuerst den Kurzzeitcache
		if (isset($this->messages[$msgid])) {
			return $this->messages[$msgid];
		}
		// Versuche die Nachricht frisch zu laden
		$messageids = $this->getMessageIDArtNrs();
		if (isset($messageids[$msgid])) {
			$artnr = $messageids[$msgid];
			// Lade die Nachricht und Parse sie
			$article = $this->nntpclient->getArticle($artnr);
			if (PEAR::isError($article)) {
				throw new NotFoundMessageException($artnr, $this->group);
			}
			$message = NNTPMessage::parsePlain(implode("\r\n", $article));
			$message = $message->getObject($this);
			
			// Schreibe die Nachricht in den Kurzzeit-Cache
			$this->messages[$msgid] = $message;

			return $message;
		}
		// Diese Nachricht gibt es offensichtlich nicht mehr ;)
		throw new NotFoundMessageException($msgid, $this->group);
	}

	/**
	 * Schreibe eine Nachricht
	 **/
	public function postMessage($message) {
		return $this->post(NNTPMessage::parseObject($this->group, $message));
	}
	public function postAcknowledge($ack, $message) {
		return $this->post(NNTPMessage::parseAcknowledgeObject($this->group, $ack, $message));
	}
	public function postCancel($cancel, $message) {
		return $this->post(NNTPMessage::parseCancelObject($this->group, $cancel, $message));
	}
	private function post($nntpmsg) {
		if (($ret = $this->nntpclient->post($nntpmsg->getPlain())) instanceof PEAR_Error) {
			/* Bekannte Fehler */
			switch ($ret->getCode()) {
			case 440:
				throw new PostingNotAllowedException($this->group, $ret);
			case 441:
				// Nachricht Syntaktisch inkorrekt
				throw new PostingFailedException($this->group, $ret);
			}
			// Ein unerwarteter Fehler - wie spannend *g*
			throw new PostingException($this->group, "#" . $ret->getCode() . ": " . $ret->getUserInfo());
		}
		// Gebe "m" zurueck, falls die Gruppe moderiert ist
		return $this->mode;
	}
}

?>
