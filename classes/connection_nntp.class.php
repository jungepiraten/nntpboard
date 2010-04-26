<?php

require_once("Net/NNTP/Client.php");

require_once(dirname(__FILE__)."/connection.abstract.class.php");
require_once(dirname(__FILE__)."/address.class.php");
require_once(dirname(__FILE__)."/thread.class.php");
require_once(dirname(__FILE__)."/message.class.php");
require_once(dirname(__FILE__)."/header.class.php");
require_once(dirname(__FILE__)."/bodypart.class.php");

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

	private $threads = null;
	private $messages = array();
	private $articles = array();

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
		
		// TODO Versuche, die ArtikelNummer herauszufinden, wenn $i eine MessageID war
		$artnr = $i;

		$header_lines = $this->nntpclient->getHeader($artnr);
		$header = $this->parseHeaderLines($header_lines);

		$messageid = isset($header["message-id"]) ? $header["message-id"]->getValue() : null;
		$subject = isset($header["subject"]) ? $header["subject"]->getValue() : null;
		$date = isset($header["date"]) ? strtotime($header["date"]->getValue()) : null;
		$sender = isset($header["from"]) ? $this->parseAddress(array_shift(explode(",", $header["from"]->getValue()))) : null;
		$charset = isset($header["content-type"]) && $header["content-type"]->hasExtra("charset") ? $header["content-type"]->getExtra("charset") : "UTF-8";

		// Sperre die Cache-Eintraege, um Endlos-Schleifen zu verhindern
		$this->messages[$artnr] = false;
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
		
		$message = new Message($this->group->getGroup(), $artnr, $messageid, $date, $sender, $subject, $charset, $threadid, $parentid);
		
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
		
		$this->messages[$artnr] = $message;
		$this->messages[$message->getMessageID()] = $message;
		return $message;
	}

	protected function getLastThread() {
		if (!isset($this->threads)) {
			$this->initThreads();
		}
		if (empty($this->threads)) {
			throw new Exception("No Thread found!");
		}
		// Wir nehmen an, dass die Threads sortiert sind ...
		return array_shift(array_slice($this->threads, 0, 1));
	}

	public function getThreads() {
		if (!isset($this->threads)) {
			$this->initThreads();
		}
		return $this->threads;
	}

	public function getThread($threadid) {
		if (!isset($this->threads)) {
			$this->initThreads();
		}
		return $this->threads[$threadid];
	}

	public function getThreadCount() {
		if (!isset($this->threads)) {
			$this->initThreads();
		}
		return count($this->threads);
	}

	public function getMessagesCount() {
		return count($this->articles);
	}

	public function getArticleNums() {
		return $this->articles;
	}

	/* *** */

	private function initThreads() {
		$this->threads = array();
		foreach ($this->articles AS $artnr) {
			$message = $this->getMessage($artnr);

			if (isset($this->threads[$message->getThreadID()])) {
				$this->threads[$message->getThreadID()]->addMessage($message);
			} else {
				$this->threads[$message->getThreadID()] = new Thread($message);
			}
		}
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

?>
