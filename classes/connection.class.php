<?php

require_once("Net/NNTP/Client.php");

require_once(dirname(__FILE__)."/address.class.php");
require_once(dirname(__FILE__)."/thread.class.php");
require_once(dirname(__FILE__)."/message.class.php");
require_once(dirname(__FILE__)."/header.class.php");
require_once(dirname(__FILE__)."/bodypart.class.php");

interface iConnection {
	public function open();
	public function close();

	public function getThreadCount();
	public function getMessagesCount();

	public function getThreads();
	public function getMessageByNum($num);
	public function getMessage($msgid);
	public function getThread($threadid);
	
	public function getLastPostMessageID();
	public function getLastPostSubject($charset = null);
	public function getLastPostDate();
	public function getLastPostAuthor($charset = null);
	public function getLastPostThreadID();
}

abstract class AbstractConnection implements iConnection {
	abstract protected function getLastThread();

	public function getLastPostMessageID() {
		try {
			return $this->getLastThread()->getLastPostMessageID();
		} catch (Exception $e) {
			return null;
		}
	}

	public function getLastPostSubject($charset = null) {
		try {
			return $this->getLastThread()->getSubject();
		} catch (Exception $e) {
			return null;
		}
	}

	public function getLastPostDate() {
		try {
			return $this->getLastThread()->getLastPostDate();
		} catch (Exception $e) {
			return null;
		}
	}

	public function getLastPostAuthor($charset = null) {
		try {
			return $this->getLastThread()->getLastPostAuthor();
		} catch (Exception $e) {
			return null;
		}
	}

	public function getLastPostThreadID() {
		try {
			return $this->getLastThread()->getThreadID();
		} catch (Exception $e) {
			return null;
		}
	}
}

if (!function_exists("decodeRFC2045")) {
	function decodeRFC2045($text, $charset) {
		preg_match_all('#=\?([-a-zA-Z0-9_]+)\?([QqBb])\?(.*)\?=(\s|$)#U', $text, $matches, PREG_SET_ORDER);
		foreach ($matches AS $m) {
			switch (strtolower($m[2])) {
			case 'b':
				$m[3] = base64_decode($m[3]);
				break;
			case 'q':
				$m[3] = str_replace("_", " ", quoted_printable_decode($m[3]));
				break;
			default:
				// prohibited by RFC2045
				continue;
			}
			$text = str_replace($m[0], iconv($m[1], $charset, $m[3]), $text);
		}
		return $text;
	}
}

class NNTPConnection extends AbstractConnection {
	private $group;
	private $username;
	private $password;

	private $articles = array();
	private $messages = array();

	private $nntpclient;
	
	public function __construct($group, $username, $password) {
		$this->group = $group;
		$this->username = $username;
		$this->password = $password;

		$this->nntpclient = new Net_NNTP_Client;
	}
	
	public function open() {
		$ret = $this->nntpclient->connect($this->group->getHost()->getHost(), false, $this->group->getHost()->getPort());
		if (PEAR::isError($ret)) {
			throw new Exception($ret);
		}
		if (!empty($this->username) && !empty($this->password)) {
			$ret = $this->nntpclient->authenticate($this->username, $this->password);
			if (PEAR::isError($ret)) {
				throw new Exception($ret);
			}
		}
		$ret = $this->nntpclient->selectGroup($this->group->getGroup(), true);
		if (PEAR::isError($ret)) {
			throw new Exception($ret);
		}
		$this->articles = $ret["articles"];
	}
	
	public function close() {
		$this->nntpclient->disconnect();
	}

	public function getMessage($msgid) {
		return $this->getMessageByNum($msgid);
	}
	
	public function getMessageByNum($i) {
		if (isset($this->messages[$i])) {
			return $this->messages[$i];
		}
		
		$header_lines = $this->nntpclient->getHeader($i);
		$header = $this->parseHeaderLines($header_lines);

		$messageid = isset($header["message-id"]) ? $header["message-id"]->getValue() : null;
		$subject = isset($header["subject"]) ? $header["subject"]->getValue() : null;
		$date = isset($header["date"]) ? strtotime($header["date"]->getValue()) : null;
		$sender = isset($header["from"]) ? $this->parseAddress(array_shift(explode(",", $header["from"]->getValue()))) : null;
		$charset = isset($header["content-type"]) && $header["content-type"]->hasExtra("charset") ? $header["content-type"]->getExtra("charset") : "UTF-8";

		$this->messages[$i] = false;
		$this->messages[$messageid] = false;

		/** Thread finden **/
		// Default: Neuer Thread
		$threadid = $messageid;
		$parentid = null;

		// Zuerst via References-Header
		if (isset($header["references"]) && trim($header["references"]->getValue()) != "") {
			$references = explode(" ", preg_replace("#\s+#", " ", $header["references"]->getValue()));
			$parentid = array_pop($references);
			$message = $this->getMessage($parentid);
			if ($message != null) {
				$threadid = $message->getThreadID();
			}
		}

		// Der XRef-Header kann die Daten jetzt noch mal ueberschreiben
		$xref = isset($header["xref"]) ? preg_match("#^(.*) (.*):([0-9]*)$#U", $header["xref"]->getValue(), $m) : null;
		if ($xref != null) {
			$message = $this->getMessageByNum($m[3]);
			if ($message != null) {
				$threadid = $message->getThreadID();
				$parentid = $message->getMessageID();
			}
		}
		
		$message = new Message($this->group->getGroup(), $i, $messageid, $date, $sender, $subject, $charset, $threadid, $parentid);
		
		/** Strukturanalyse des Bodys **/
		$body = implode("\n", $this->nntpclient->getBody($messageid));
		/** MIME-Handling (RFC 1341) **/
		if (isset($header["content-type"])
		 && substr($header["content-type"]->getValue(),0,9) == "multipart"
		 && $header["content-type"]->hasExtra("boundary"))
		{
			$parts = explode("--" . $header["content-type"]->getExtra("boundary"), $body);
			// Der erste (This is an multipart ...) und letzte Teil (--) besteht nur aus Sinnlosem Inhalt
			array_pop($parts);
			array_shift($parts);
			foreach ($parts AS $p => $part) {
				list($part_header,$part_body) = preg_split("$\r?\n\r?\n$", $part, 2);
				$message->addBodyPart($this->parseBodyPart($message, $p, $part_header, $part_body));
			}
		} else {
			$message->addBodyPart($this->parseBodyPart($message, 0, $header_lines, $body));
		}
		
		$this->messages[$i] = $message;
		$this->messages[$message->getMessageID()] = $message;
		return $message;
	}

	protected function getLastThread() {
		// TODO
		throw new Exception("Not Implemented");
	}

	public function getThreads() {
		// TODO
		throw new Exception("Not Implemented");
	}

	public function getThread($threadid) {
		// TODO
		throw new Exception("Not Implemented");
	}

	public function getThreadCount() {
		// TODO
		throw new Exception("Not Implemented");
	}

	public function getMessagesCount() {
		// TODO
		throw new Exception("Not Implemented");
	}

	/* *** */

	public function getArticles() {
		return $this->articles;
	}

	private function parseHeaderLines($header_data) {
		if (!is_array($header_data)) {
			$header_data = preg_split("$\r?\n$", $header_data);
		}
		// Wir nehmen einfach mal UTF-8 an
		$charset = "UTF-8";
		$header = array();
		for ($i=0; $i<count($header_data); $i++) {
			$line = $header_data[$i];
			// Multiline-Header
			while (isset($header_data[$i+1]) && preg_match("$^\s$", $header_data[$i+1])) {
				$line .= " ".ltrim($header_data[++$i]);
			}

			list($name, $value) = explode(":", $line, 2);
			$extras = explode(";", $value);
			$h = new Header(trim($name), decodeRFC2045(trim(array_shift($extras)), $charset), $charset);
			foreach ($extras AS $extra) {
				list($name, $value) = explode("=", $extra, 2);
				$name = decodeRFC2045(trim($name), $h->getCharset());
				$value = decodeRFC2045(trim($value, "\"\' \t"), $h->getCharset());
				$h->addExtra($name, $value);
			}

			$header[strtolower($h->getName())] = $h;
		}
		return $header;
	}

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
		return new Address($name, $addr, $comment);
	}
	
	private function parseBodyPart($message, $partid, $header, $body) {
		$header = $this->parseHeaderLines($header, $charset);

		$charset = "UTF8";
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
			// No encoding
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
		
		return new BodyPart($message, $partid, $disposition, $mimetype, $body, $charset, $filename);
	}
}

class CacheConnection extends AbstractConnection {
	private $group;
	private $datadir;

	// Alle Nachrichten als Message-Objekt
	private $messages = array();
	// Zuordnung MSGID => THREADID
	private $threadids = array();
	// Zuordnung ArtikelNr => MSGID
	private $articlenums = array();
	// Alle Threads als Thread-Objekt (ohne Nachrichten)
	private $threads = array();
	
	private $lastarticlenr = 0;
	
	public function __construct($group, $datadir) {
		$this->group = $group;
		$this->datadir = $datadir;
	}
	
	public function open() {
		if (file_exists($this->datadir->getGroupPath($this->group))) {
			$data = unserialize(file_get_contents($this->datadir->getGroupPath($this->group)));
			$this->threadids	= $data["threadids"];
			$this->articlenums	= $data["articlenums"];
			$this->threads		= $data["threads"];
			$this->lastarticlenr	= $data["lastarticlenr"];

			/**
			 * Lade Threads erst nach und nach, um Weniger Last zu verursachen
			 * vgl loadThreadMessages($threadid)
			 **/
		}
	}

	public function close() {
		$data = array(
			"threadids"	=> $this->threadids,
			"articlenums"	=> $this->articlenums,
			"threads"	=> $this->threads,
			"lastarticlenr"	=> $this->lastarticlenr);
		
		file_put_contents($this->datadir->getGroupPath($this->group), serialize($data));
		
		// Speichere die Nachrichten Threadweise
		foreach ($this->threads AS $threadid => $thread) {
			$messageids = $thread->getMessages();
			$messages = array();
			// Attachments speichern
			foreach ($messageids AS $messageid) {
				$message = $this->getMessage($messageid);
				$message->saveAttachments($this->datadir);
				$messages[$messageid] = $message;
			}
			
			$filename = $this->datadir->getThreadPath($this->group, $thread);
			file_put_contents($filename, serialize($messages));
		}
	}

	public function getMessageByNum($num) {
		if (isset($this->articlenums[$num])) {
			return $this->getMessage($this->articlenums[$num]);
		}
		return null;
	}

	public function getMessage($messageid) {
		if (isset($this->messages[$messageid])) {
			return $this->messages[$messageid];
		}
		$message = null;
		if (!empty($this->threadids[$messageid])) {
			$this->loadThreadMessages($this->threadids[$messageid]);
			$message = $this->messages[$messageid];
		}
		$this->messages[$messageid] = $message;
		return $message;
	}

	public function getThreads() {
		return $this->threads;
	}

	public function getThread($threadid) {
		return $this->threads[$threadid];
	}

	public function getThreadCount() {
		return count($this->threads);
	}

	public function getMessagesCount() {
		return count($this->messages);
	}

	protected function getLastThread() {
		if (empty($this->threads)) {
			throw new Exception("No Thread found!");
		}
		// Wir nehmen an, dass die Threads sortiert sind ...
		return array_shift(array_slice($this->threads, 0, 1));
	}

	/* ****** */
	
	public function loadMessages($connection) {
		$articles = $connection->getArticles();
		
		foreach ($articles as $articlenr) {
			if ($articlenr > $this->getLastArticleNr()) {
				$this->addMessage($connection->getMessage($articlenr));
			}
		}
		$this->sort();
	}
	
	public function sort() {
		// Sortieren
		if (!function_exists("cmpThreads")) {
			function cmpThreads($a, $b) {
				return $b->getLastPostDate() - $a->getLastPostDate();
			}
		}
		uasort($this->threads, cmpThreads);
	}

	private function loadThreadMessages($threadid) {
		if ($this->getThread($threadid) === null) {
			return;
		}
		
		$filename = $this->datadir->getThreadPath( $this->group , $this->getThread($threadid) );
		if (!file_exists($filename)) {
			throw new Exception("Thread {$threadid} in Group {$this->getGroup} not yet initialized!");
		}
		$messages = unserialize(file_get_contents($filename));
		foreach ($messages AS $message) {
			$this->addMessage($message);
		}
	}
	
	public function addMessage($message) {
		// Speichere die Nachricht
		$this->messages[$message->getMessageID()] = $message;
		$this->threadids[$message->getMessageID()] = $message->getThreadID();
		$this->articlenums[$message->getArticleNum()] = $message->getMessageID();

		// Ist Unterpost
		if ($message->hasParentID() && isset($this->messages[$message->getParentID()])) {
			$this->getMessage($message->getParentID())->addChild($message);
		}
		
		// Thread erstellen oder in Thread einordnen
		if (!isset($this->threads[$message->getThreadID()])) {
			$this->addThread(new Thread($message));
		}
		$this->getThread($message->getThreadID())->addMessage($message);

		// Letzte Artikelnummer updaten
		if ($message->getArticleNum() > $this->lastarticlenr) {
			$this->lastarticlenr = $message->getArticleNum();
		}
	}
	
	private function addThread($thread) {
		$this->threads[$thread->getThreadID()] = $thread;
	}

	public function getLastArticleNr() {
		return $this->lastarticlenr;
	}
}

?>
