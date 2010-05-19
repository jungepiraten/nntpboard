<?php

// http://pear.php.net/package/Net_NNTP
require_once("Net/NNTP/Client.php");

require_once(dirname(__FILE__)."/../connection.class.php");
require_once(dirname(__FILE__)."/../address.class.php");
require_once(dirname(__FILE__)."/../thread.class.php");
require_once(dirname(__FILE__)."/../message.class.php");
require_once(dirname(__FILE__)."/../attachment.class.php");
require_once(dirname(__FILE__)."/nntp/header.class.php");
require_once(dirname(__FILE__)."/nntp/message.class.php");
require_once(dirname(__FILE__)."/../exceptions/group.exception.php");

class NNTPConnection extends AbstractConnection {
	private $host;
	private $group;

	// MessageID => ArtikelNum
	private $messageids = array();
	// y (read-write), n (read-only) oder m (moderiert)
	private $mode = null;

	private $nntpclient;
	
	public function __construct(Host $host, $group) {
		parent::__construct();

		$this->host = $host;
		$this->group = $group;
		$this->nexthop = $uplink;

		// Verbindung initialisieren
		$this->nntpclient = new Net_NNTP_Client;
	}
	
	public function open($auth = null) {
		// Verbindung oeffnen
		$ret = $this->nntpclient->connect($this->host->getHost(), false, $this->host->getPort());
		if (PEAR::isError($ret)) {
			throw new Exception($ret);
		}
		
		// ggf. Authentifieren
		if (isset($auth)) {
			$ret = $this->nntpclient->authenticate($auth->getNNTPUsername(), $auth->getNNTPPassword());
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
		// Hole eine Uebersicht ueber alle verfuegbaren Posts
		$articles = $this->nntpclient->getOverview($ret["first"]."-".$ret["last"]);
		if (PEAR::isError($articles)) {
			throw new Exception($ret);
		} else {
			foreach ($articles AS $article) {
				$this->messageids[$article["Message-ID"]] = $article["Number"];
			}
		}
	}
	
	public function close() {
		$this->nntpclient->disconnect();
	}


	public function getMessageIDs() {
		return array_keys($this->messageids);
	}
	public function getMessageCount() {
		return count($this->messageids);
	}
	public function hasMessage($msgid) {
		return isset($this->messageids[$msgid]);
	}
	public function getMessage($msgid) {
		// Frage zuerst den Kurzzeitcache
		if (isset($this->messages[$msgid])) {
			return $this->messages[$msgid];
		}
		// Versuche die Nachricht frisch zu laden
		if (isset($this->messageids[$msgid])) {
			$artnr = $this->messageids[$msgid];
			// Lade die Nachricht und Parse sie
			$article = $this->nntpclient->getArticle($artnr);
			if (PEAR::isError($article)) {
				throw new NotFoundMessageException($artnr, $this->group);
			}
			$message = NNTPMessage::parsePlain(implode("\r\n", $article));
			$message = $message->getObject();
			
			// Schreibe die Nachricht in den Kurzzeit-Cache
			$this->messages[$msgid] = $message;

			return $message;
		}
		// Letzter Versuch: rekursiv
		if ($this->nexthop !== null) {
			return $this->nexthop->getMessage($msgid);
		}
		// Diese Nachricht gibt es offensichtlich nicht mehr ;)
		throw new NotFoundMessageException($msgid, $this->group);
	}

	protected function mayRead() {
		return true;
	}

	protected function mayPost() {
		return $this->mode != "n";
	}

	protected function isModerated() {
		return $this->mode == "m";
	}

	public function getGroup() {
		$group = parent::getGroup();
		foreach ($this->getMessageIDs() as $messageid) {
			$group->addMessage($this->getMessage($messageid));
		}
		return $group;
	}

	/**
	 * Schreibe eine Nachricht
	 **/
	public function post($message) {
		$nntpmsg = NNTPMessage::parseObject($message);
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
	}
}

?>
