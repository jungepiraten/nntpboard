<?php

// http://pear.php.net/package/Net_NNTP
require_once("Net/NNTP/Client.php");

require_once(dirname(__FILE__)."/../connection.class.php");
require_once(dirname(__FILE__)."/../address.class.php");
require_once(dirname(__FILE__)."/../thread.class.php");
require_once(dirname(__FILE__)."/../message.class.php");
require_once(dirname(__FILE__)."/../bodypart.class.php");
require_once(dirname(__FILE__)."/nntp/header.class.php");
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
			$message = $this->parseMessage($artnr, implode("\r\n", $article));
			
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
	 * Parse die Header in einen Array von Header-Objekten [X]
	 **/
	private function parseHeaderLines($header_data) {
		if (!is_array($header_data)) {
			$header_data = explode("\n", $header_data);
		}
		/**
		 * Wir nehmen einfach mal UTF-8 an (die Header-Klasse speichert
		 * ihren Zeichensatz mit, weshalb hier jeder ASCII-Zeichensatz passen wuerde)
		 **/
		$charset = "UTF-8";
		mb_internal_encoding($charset);
		$header = array();
		for ($i=0; $i<count($header_data); $i++) {
			// Eventuellen Zeilenruecklauf abschneiden
			$line = rtrim($header_data[$i]);
			// Multiline-Header
			while (isset($header_data[$i+1]) && preg_match("$^\s$", $header_data[$i+1])) {
				$line .= " ".ltrim($header_data[++$i]);
			}

			$h = NNTPHeader::parsePlain($line);

			$header[strtolower($h->getName())] = $h;
		}
		return $header;
	}
	
	/**
	 * Parst einen Kompletten Artikel (inkl. (mehreren) Body(s) und allen Headern)
	 * gibt ein Message-Objekt zurueck
	 * @param	int	$artnr
	 * @param	string	$body
	 *		Nachricht
	 * @return	Message
	 **/
	private function parseMessage($artnr, $article) {
		/* Nachricht */
		list($header, $body) = preg_split("$\r?\n\r?\n$", $article, 2);
		$header = $this->parseHeaderLines($header);

		/* Lese Header */
		$messageid = isset($header["message-id"]) ? $header["message-id"]->getValue() : null;
		$subject = isset($header["subject"]) ? $header["subject"]->getValue() : null;
		$date = isset($header["date"]) ? strtotime($header["date"]->getValue()) : null;
		$sender = isset($header["from"]) ? $this->parseAddress(array_shift(explode(",", $header["from"]->getValue()))) : null;
		$charset = isset($header["content-type"]) && $header["content-type"]->hasExtra("charset") ? $header["content-type"]->getExtra("charset") : "UTF-8";

		// Sperre den Cache-Eintrag, um eine Endlosschleife zu vermeiden
		$this->messages[$messageid] = false;

		/* Thread finden */

		// Default: Neuer Thread
		$parentid = null;

		// References
		if (isset($header["references"]) && trim($header["references"]->getValue()) != "") {
			$references = explode(" ", preg_replace("#\s+#", " ", $header["references"]->getValue()));
			$parentid = array_pop($references);
		}
		
		/* MIME-Nachrichten */
		$mimetype = null;
		if (isset($header["content-type"])
		 && substr($header["content-type"]->getValue(),0,9) == "multipart") {
			$mimetype = substr($header["content-type"]->getValue(),10);
		}
		
		$message = new Message($this->group->getGroup(), $artnr, $messageid, $date, $sender, $subject, $charset, $parentid, $mimetype);
		
		/* Strukturanalyse des Bodys */
		if ($mimetype != null && $header["content-type"]->hasExtra("boundary")) {
			$parts = explode("--" . $header["content-type"]->getExtra("boundary"), $body);
			// Der erste (This is an multipart ...) und letzte Teil (--) besteht nur aus Sinnlosem Inhalt
			array_pop($parts);
			array_shift($parts);
			
			foreach ($parts AS $part) {
				$message->addBodyPart($this->parseBodyPart($message, $part));
			}
		} else {
			$message->addBodyPart($this->parseBodyPart($message, $article));
		}
		
		return $message;
	}

	/**
	 * Parse die Adresse von "Name <mailadresse> (Kommentar)"
	 * @return	Address
	 **/
	private function parseAddress($addr) {
		if (preg_match('/^(.*) \((.*?)\)\s*$/', $addr, $m)) {
			array_shift($m);
			$addr = trim(array_shift($m));
			$comment = trim(array_shift($m));
		}
		if (preg_match('/^(.*) <(.*)>\s*$/', $addr, $m)) {
			array_shift($m);
			$name = trim(array_shift($m)," \"'\t");
			$addr = trim(array_shift($m));
		}
		return new Address($name, trim($addr, "<>"), $comment);
	}
	
	/**
	 * Parst einen BodyPart
	 * @return	BodyPart
	 * TODO Mime-Parsing komplett aendern
	 **/
	private function parseBodyPart($message, $part) {
		list($header, $body) = preg_split("$\r?\n\r?\n$", $part, 2);
		$header = $this->parseHeaderLines($header);

		// Per default nehmen wir UTF-8 (warum auch was anderes?)
		$charset = "UTF-8";
		if (isset($header["content-type"]) && $header["content-type"]->hasExtra("charset")) {
			$charset = $header["content-type"]->getExtra("charset");
		}
		
		/** See RFC 2045 / Section 6.1. **/
		$encoding = "7bit";
		if (isset($header["content-transfer-encoding"])) {
			$encoding = strtolower($header["content-transfer-encoding"]->getValue());
		}
		switch ($encoding) {
		default:
		case "7bit":
		case "8bit":
		case "binary":
			// No encoding => Do nothing
			break;
		case "quoted-printable":
			$body = quoted_printable_decode($body);
			break;
		case "base64":
			$body = base64_decode($body);
			break;
		}

		/** Mime-Type **/
		$mimetype = "text/plain";
		if (isset($header["content-type"])) {
			$mimetype = $header["content-type"]->getValue();
		}
		
		/** Disposition **/
		$disposition = "inline";
		$filename = null;
		if (isset($header["content-disposition"])) {
			$disposition = $header["content-disposition"]->getValue();
			if ($header["content-disposition"]->hasExtra("filename")) {
				$filename = $header["content-disposition"]->getExtra("filename");
			}
		}
		
		return new BodyPart($message, $disposition, $mimetype, $body, $charset, $filename);
	}

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
