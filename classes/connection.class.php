<?php

require_once("Net/NNTP/Client.php");
require_once(dirname(__FILE__)."/address.class.php");
require_once(dirname(__FILE__)."/message.class.php");
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

class Header {
	private $name;
	private $value;
	private $charset;
	private $extra = array();

	public function __construct($name, $value, $charset) {
		$this->name = $name;
		$this->value = $value;
		$this->charset = $charset;
	}

	public function getName() {
		return $this->name;
	}

	public function getValue() {
		return $this->value;
	}

	public function addExtra($name, $value) {
		$this->extra[strtolower($name)] = $value;
	}

	public function hasExtra($name) {
		return isset($this->extra[strtolower($name)]);
	}

	public function getExtra($name) {
		return $this->extra[strtolower($name)];
	}
}

class NNTPConnection extends Net_NNTP_Client {
	private $group;
	private $username;
	private $password;
	
	public function __construct($group, $username, $password) {
		parent::__construct();
		$this->group = $group;
		$this->username = $username;
		$this->password = $password;
	}
	
	public function open() {
		$ret = $this->connect($this->group->getHost()->getHost(), false, $this->group->getHost()->getPort());
		if (PEAR::isError($ret)) {
			throw new Exception($ret);
		}
		if (!empty($this->username) && !empty($this->password)) {
			$ret = $this->authenticate($this->username, $this->password);
			if (PEAR::isError($ret)) {
				throw new Exception($ret);
			}
		}
		$ret = $this->selectGroup($this->group->getGroup(), true);
		if (PEAR::isError($ret)) {
			throw new Exception($ret);
		}
	}
	
	public function close() {
		$this->disconnect();
	}
	
	public function loadMessages($charset) {
		$ret = $this->selectGroup($this->group->getGroup(), true);
		if (PEAR::isError($ret)) {
			throw new Exception($ret);
		}
		$articles = $ret["articles"];
		
		foreach ($articles as $articlenr) {
			if ($articlenr > $this->group->getLastArticleNr()) {
				$this->group->addMessage($this->getMessage($articlenr, $charset));
			}
		}
		$this->group->sort();
	}
	
	public function getMessage($i, $charset) {
		$header_lines = $this->getHeader($i);
		$header = $this->parseHeaderLines($header_lines, $charset);

		$messageid = isset($header["message-id"]) ? $header["message-id"]->getValue() : null;
		$subject = isset($header["subject"]) ? $header["subject"]->getValue() : null;
		$date = isset($header["date"]) ? strtotime($header["date"]->getValue()) : null;
		$sender = isset($header["from"]) ? $this->parseAddress(array_shift(explode(",", $header["from"]->getValue()))) : null;

		/** Thread finden **/
		// Default: Neuer Thread
		$threadid = $messageid;
		$parentid = null;

		// Zuerst via References-Header
		if (isset($header["references"]) && trim($header["references"]->getValue()) != "") {
			$references = explode(" ", preg_replace("#\s+#", " ", $header["references"]->getValue()));
			$parentid = array_pop($references);
			$message = $this->group->getMessage($parentid);
			if ($message != null) {
				$threadid = $message->getThreadID();
			}
		}

		// Der XRef-Header kann die Daten jetzt noch mal ueberschreiben
		$xref = isset($header["xref"]) ? preg_match("#^(.*) (.*):([0-9]*)$#U", $header["xref"]->getValue(), $m) : null;
		if ($xref != null) {
			$message = $this->group->getMessageByNum($m[3]);
			if ($message != null) {
				$threadid = $message->getThreadID();
				$parentid = $message->getMessageID();
			}
		}
		
		$message = new Message($this->group->getGroup(), $i, $messageid, $date, $sender, $subject, $charset, $threadid, $parentid);
		
		/** Strukturanalyse des Bodys **/
		$body = implode("\n", $this->getBody($messageid));
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
			$message->addBodyPart($this->parseBodyPart($message, 0, $header_lines, $body, $charset));
		}
		
		return $message;
	}

	private function parseHeaderLines($header_data, $charset) {
		if (!is_array($header_data)) {
			$header_data = preg_split("$\r?\n$", $header_data);
		}
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
				$h->addExtra(trim($name), trim($value,"\"\' \t"));
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
