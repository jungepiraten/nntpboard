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

if (!function_exists("quoted_printable_encode")) {
	// aus http://de.php.net/quoted_printable_decode
	function quoted_printable_encode($string) {
		$string = str_replace(array('%20', '%0D%0A', '%'), array(' ', "\r\n", '='), rawurlencode($string));
		$string = preg_replace('/[^\r\n]{73}[^=\r\n]{2}/', "$0=\r\n", $string);
		return $string;
	}
}

class NNTPConnection extends AbstractConnection {
	private $group;
	private $username;
	private $password;

	// ThreadID => Thread | Muss null sein - wird erst spaeter initialisiert (vgl. initThreads())
	private $threads = null;
	// MessageID => ArtikelNum
	private $messageids = array();
	// MessageID => Message
	private $messages = array();

	private $nntpclient;
	
	public function __construct($group, $auth) {
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


	protected function getLastThread() {
		// Initialisiere die Threads, falls noch nicht geschehen
		if (!isset($this->threads)) {
			$this->initThreads();
		}
		// Wenn wir noch immer keine Threads finden koennen, haben wir wohl keine :(
		if (empty($this->threads)) {
			throw new EmptyGroupException($this->group);
		}
		// Wir nehmen an, dass die Threads sortiert sind ...
		return array_shift(array_slice($this->threads, 0, 1));
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


	public function getThreadIDs() {
		if (!isset($this->threads)) {
			$this->initThreads();
		}
		return array_keys($this->threads);
	}

	public function getThreadCount() {
		if (!isset($this->threads)) {
			$this->initThreads();
		}
		return count($this->threads);
	}

	public function hasThread($threadid) {
		// Wenn die Threads noch nicht initalisiert sind, nehmen wir an,
		// dass wir diesen Thread nicht haben
		if (!isset($this->threads)) {
			return false;
		}
		return isset($this->threads[$threadid]);
	}

	public function getThread($threadid) {
		if (!isset($this->threads)) {
			$this->initThreads();
		}
		return $this->threads[$threadid];
	}


	public function post($message) {
		if (($ret = $this->nntpclient->post($this->generateMessage($message))) instanceof PEAR_Error) {
			/* Bekannte Fehler */
			switch ($ret->getCode()) {
			case 440:
				throw new PostingNotAllowedException($this->group->getGroup());
			}
			// Ein unerwarteter Fehler - wie spannend *g*
			throw new PostException($this->group, "#" . $ret->getCode() . ": " . $ret->getUserInfo());
		}
	}

	/**
	 * Initialisiere den Thread-Array
	 * Dafuer muessen wir ALLE nachrichten laden und sortieren :(
	 * TODO brauchen wir hier ueberhaupt Threads?
	 **/
	private function initThreads() {
		$this->threads = array();
		foreach ($this->getMessageIDs() AS $msgid) {
			$message = $this->getMessage($msgid);

			// Entweder Unterpost oder neuen Thread starten
			if ($message->hasParent() && $this->hasMessage($message->getParentID())) {
				$this->getMessage($message->getParentID())->addChild($message);
				$threadid = $this->threadids[$message->getParentID()];
			} else {
				$thread = new Thread($message);
				$this->addThread($thread);
				$threadid = $thread->getThreadID();
			}

			// Nachricht zum Thread hinzufuegen
			$this->getThread($threadid)->addMessage($message);
		}
		// TODO sortieren
	}

	/**
	 * KONVERT-FUNKTIONEN
	 * TODO: Auslagern? in einzelne NNTP-Klassen
	 **/

	/**
	 * Baut aus einem Message-Objekt wieder eine Nachricht
	 **/
	private function generateMessage($message) {
		$charset = $message->getCharset();
		$crlf = "\r\n";
		
		/**
		 * Wichtig: In den Headern darf nur 7-bit-Codierung genutzt werden.
		 * Alles andere muss Codiert werden (vgl. mb_encode_mimeheader() )
		 **/
		mb_internal_encoding($charset);
		
		/* Standart-Header */
		$data  = "Message-ID: " . $message->getMessageID() . $crlf;
		$data .= "From: " . $this->generateAddress($message->getAuthor()) . $crlf;
		$data .= "Date: " . date("r", $message->getDate()) . $crlf;
		$data .= "Subject: " . mb_encode_mimeheader($message->getSubject($charset), $charset) . $crlf;
		$data .= "Newsgroups: " . $message->getGroup() . $crlf;
		if ($message->hasParent()) {
			$data .= "References: " . $message->getParentID() . $crlf;
		}
		$data .= "User-Agent: " . "MessageConverter" . $crlf;
		if ($message->isMime()) {
			/* MIME-Header */
			// Generiere den Boundary - er sollte _nicht_ im Text vorkommen
			$boundary = "--" . md5(uniqid());
			$data .= "Content-Type: multipart/" . $message->getMimeType() . "; boundary=\"" . addcslashes($boundary, "\"") . "\"" . $crlf;
			$data .= $crlf;
			$data .= "This is a MIME-Message." . $crlf;

			$parts = $message->getBodyParts();
		} else {
			// Sicherstellen, dass wir nur einen BodyPart fuer Nicht-MIME-Nachrichten haben
			$parts = array( array_shift($message->getBodyParts()) );
		}
		
		$disposition = false;
		foreach ($parts AS $part) {
			// MIME-Boundary nur, wenn die Nachricht MIME ist
			if ($message->isMime()) {
				$data .= "--" . $boundary . $crlf;
			}
			$data .= $this->generateBodyPart($part);
		}
		// MIME-Abschluss einbringen
		if ($message->isMime()) {
			$data .= "--" . $boundary . "--" . $crlf;
		}

		return $data;
	}

	/**
	 * Wandle ein Address-Objekt in einen String um
	 **/
	private function generateAddress($addr) {
		return ($addr->hasName() ? "{$addr->getName()} <{$addr->getAddress()}>" : $addr->getAddress()) . ($addr->hasComment() ? " ({$addr->getComment()})" : "");
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
