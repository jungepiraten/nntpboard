<?php

// http://pear.php.net/package/Net_NNTP
require_once("Net/NNTP/Client.php");

require_once(dirname(__FILE__)."/../uplink.class.php");
require_once(dirname(__FILE__)."/../address.class.php");
require_once(dirname(__FILE__)."/../thread.class.php");
require_once(dirname(__FILE__)."/../message.class.php");
require_once(dirname(__FILE__)."/../attachment.class.php");
require_once(dirname(__FILE__)."/nntp/header.class.php");
require_once(dirname(__FILE__)."/nntp/message.class.php");
require_once(dirname(__FILE__)."/../exceptions/group.exception.php");

class NNTPUplink extends AbstractUplink {
	private $group;
	private $username;
	private $password;

	// MessageID => ArtikelNum
	private $messageids = array();
	// MessageID => Message
	private $messages = array();

	private $nntpclient;
	
	public function __construct(NNTPGroup $group, $auth) {
		parent::__construct();

		$this->group = $group;
		// NNTP-Zugangsdaten holen
		if ($auth instanceof Auth) {
			$this->username = $auth->getNNTPUsername();
			$this->password = $auth->getNNTPPassword();
		}

		// Verbindung initialisieren
		$this->nntpclient = new Net_NNTP_Client;
	}
	
	public function open() {
		// Verbindung oeffnen
		$ret = $this->nntpclient->connect($this->group->getHost()->getHost(), false, $this->group->getHost()->getPort());
		if (PEAR::isError($ret)) {
			throw new Exception($ret);
		}
		
		// ggf. Authentifieren
		if (!empty($this->username) && !empty($this->password)) {
			$ret = $this->nntpclient->authenticate($this->username, $this->password);
			if (PEAR::isError($ret)) {
				throw new Exception($ret);
			}
		}

		// Zugriffsrechte laden
		$ret = $this->nntpclient->getGroups($this->group->getGroup());
		if (PEAR::isError($ret)) {
			throw new Exception($ret);
		}
		if (!isset($ret[$this->group->getGroup()])) {
			throw new Exception("Reading not allowed!");
		}
		// y, n or m / TODO: benutze diese informationen irgendwie sinnvoll
		$posting = $ret[$this->group->getGroup()]["posting"];
		
		// Waehle die passende Gruppe aus
		// Hole Zuordnung ArtNr <=> MessageID
		$ret = $this->nntpclient->selectGroup($this->group->getGroup(), true);
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
		// Versuche die nachricht frisch zu laden
		if (isset($this->messageids[$msgid])) {
			$artnr = $this->messageids[$msgid];
			// Lade die Nachricht und Parse sie
			$article = $this->nntpclient->getArticle($artnr);
			if (PEAR::isError($article)) {
				throw new NotFoundMessageException($artnr, $this->group);
			}
			$message = NNTPMessage::parsePlain($this->group->getGroup(), $artnr, implode("\r\n", $article));
			$message = $message->getObject();
			
			// Schreibe die Nachricht in den Kurzzeit-Cache
			$this->messages[$msgid] = $message;

			return $message;
		}
		throw new NotFoundMessageException($msgid, $this->group);
	}


	public function post($message) {
		$nntpmsg = NNTPMessage::parseObject($message);
		if (($ret = $this->nntpclient->post($nntpmsg->getPlain())) instanceof PEAR_Error) {
			/* Bekannte Fehler */
			switch ($ret->getCode()) {
			case 440:
				throw new PostingNotAllowedException($this->group->getGroup());
			case 441:
				// Nachricht Syntaktisch Inkorrekt -.- (TODO)
			}
			// Ein unerwarteter Fehler - wie spannend *g*
			throw new PostException($this->group, "#" . $ret->getCode() . ": " . $ret->getUserInfo());
		}
	}

	/**
	 * Baut aus einem Message-Objekt wieder eine Nachricht
	 **/
	private function generateBodyPart($part) {
		$charset = $part->getCharset();
		$crlf = "\r\n";
	
		$data  = "Content-Type: " . $part->getMimeType() . "; Charset=\"" . addcslashes($charset, "\"") . "\"" . $crlf;
		// Der Erste Abschnitt kriegt keinen Disposition-Header (Haupttext)
		if ($disposition) {
			$data .= "Content-Disposition: " . $part->getDisposition() . ($part->hasFilename() ? "; filename=\"".addcslashes($part->getFilename(), "\"")."\"" : "") . $crlf;
			$disposition = true;
		}
		// Waehle das Encoding aus - Base64 geht immer, aber fuer Text ist quoted-printable doch schoener
		$encoding = "base64";
		if ($part->isText()) {
			$encoding = "quoted-printable";
		}
		$data .= "Content-Transfer-Encoding: " . $encoding . $crlf;
		$data .= $crlf;

		/* Body */
		$body = $part->getText($charset);
		switch ($encoding) {
		case "7bit":
		case "8bit":
		case "binary":
			// Do nothing!
			break;
		case "base64":
			$body = chunk_split(base64_encode($body), 76, $crlf);
			break;
		case "quoted-printable":
			$body = quoted_printable_encode($body);
			break;
		}
		
		$data .= rtrim($body, $crlf) . $crlf;
		$data .= $crlf;
		return $data;
	}
}

?>
