<?php

// http://pear.php.net/package/Net_NNTP
require_once("Net/NNTP/Client.php");

require_once(dirname(__FILE__)."/rfc5322.class.php");
require_once(dirname(__FILE__)."/../../exceptions/group.exception.php");

class NNTPConnection extends AbstractRFC5322Connection {
	private $host;
	private $group;

	// Erste ArtNr und Letzte ArtNr
	private $messageCount;
	private $firstartnr;
	private $lastartnr;
	// MessageIDs
	private $messageids = null;
	// y (read-write), n (read-only) oder m (moderiert)
	private $mode = null;

	private $nntpclient;

	public function __construct(Host $host, $group, $boardindexer = null) {
		parent::__construct($boardindexer);

		$this->host = $host;
		$this->group = $group;

		// Verbindung initialisieren
		$this->nntpclient = new Net_NNTP_Client;
	}

	public function getGroupID() {
		return __CLASS__ . ":" . $this->group . "@" . $this->host;
	}

	public function open($auth) {
		// Verbindung oeffnen
		$ret = $this->nntpclient->connect($this->host->getHost(), false, $this->host->getPort());
		if (PEAR::isError($ret)) {
			throw new Exception($ret);
		}
		// Zwar unschoen, aber die PEAR-DB laesst keine andere Moeglichkeit
		$this->nntpclient->cmdModeReader();

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

		$this->refreshCache();
	}

	public function close() {
		$this->nntpclient->disconnect();
	}

	// Interne Caches leeren, damit wir merken, dass sich etwas geaendert hat
	protected function refreshCache() {
		// Waehle die passende Gruppe aus
		$ret = $this->nntpclient->selectGroup($this->group, true);
		if (PEAR::isError($ret)) {
			throw new Exception($ret);
		}
		$this->messageCount = $ret["count"];
		$this->firstartnr = $ret["first"];
		$this->lastartnr = $ret["last"];

		$this->messageids = null;
	}

	public function getMessageIDs() {
		if ($this->messageids == null) {
			// Hole eine Uebersicht ueber alle verfuegbaren Posts
			$articles = $this->nntpclient->getOverview($this->firstartnr . "-" . $this->lastartnr);
			if (PEAR::isError($articles)) {
				throw new Exception($articles);
			} else {
				$this->messageids = array();
				foreach ($articles AS $article) {
					$this->messageids[] = $article["Message-ID"];
				}
			}
		}
		return $this->messageids;
	}

	public function getMessageCount() {
		return $this->messageCount;
	}

	public function hasMessage($msgid) {
		return in_array($msgid, $this->getMessageIDs());
	}

	protected function getRFC5322Message($msgid) {
		if ($this->hasMessage($msgid)) {
			// Lade die Nachricht und Parse sie
			$article = $this->nntpclient->getArticle($msgid);
			if (PEAR::isError($article)) {
				throw new NotFoundMessageException($msgid, $this->group);
			}
			return RFC5322Message::parsePlain(implode("\r\n", $article));
		}
		// Diese Nachricht gibt es offensichtlich nicht mehr ;)
		throw new NotFoundMessageException($msgid, $this->group);
	}

	public function getMessage($msgid) {
		$message = parent::getMessage($msgid);
		// Bei "Mailman" benutzen wir lieber die Mailadresse, weil Mailingliste
		if ($rfcmessage->getHeader()->has("Sender")
		  && (strtolower($rfcmessage->getHeader()->get("Sender")->getValue("UTF-8")) != "mailman@community.junge-piraten.de")) {
			$message->setAuthor(RFC5322Address::parsePlain($rfcmessage->getHeader()->get("Sender")->getValue("UTF-8"))->getObject());
		}
		return $message;
	}

	/**
	 * Schreibe eine Nachricht
	 **/

	protected function post($nntpmsg) {
		$nntpmsg->getHeader()->setValue("Newsgroups", $this->group);

		if (($ret = $this->nntpclient->post($nntpmsg->getPlain())) instanceof PEAR_Error) {
			$errstring = "#" . $ret->getCode() . ": " . $ret->getUserInfo() . " on Message\n" . $nntpmsg->getPlain();
			/* Bekannte Fehler */
			switch ($ret->getCode()) {
			case 440:
				throw new PostingNotAllowedException($this->group, $errstring);
			case 441:
				// Nachricht Syntaktisch inkorrekt
				throw new PostingFailedException($this->group, $errstring);
			}
			// Ein unerwarteter Fehler - wie spannend *g*
			throw new PostingException($this->group, $errstring);
		}
		// u.a. um zu bemerken, dass wir einen neuen GroupCache haben
		$this->refreshCache();
		// Gebe "m" zurueck, falls die Gruppe moderiert ist
		return ($this->mode == "m" ? "m" : ($this->mode == "y"));
	}
}

?>
