<?php

class NNTPHeader {
	public static function parsePlain($plain) {
		if (!is_array($plain)) {
			$plain = explode("\r\n", $plain);
		}
		$header = new NNTPHeader;
		for ($i=0; $i<count($plain); $i++) {
			// Eventuellen Zeilenruecklauf abschneiden
			$line = rtrim($plain[$i]);
			// Multiline-Header
			while (isset($plain[$i+1]) && preg_match("$^\s$", $plain[$i+1])) {
				$line .= " ".ltrim($plain[++$i]);
			}
			$header->set(NNTPSingleHeader::parsePlain($line));
		}
		return $header;
	}

	private $headers = array();

	public function __construct() {
	}

	public function set($header) {
		$this->headers[strtolower($header->getName())] = $header;
	}

	public function has($name) {
		return isset($this->headers[strtolower($name)]);
	}

	public function get($name) {
		return $this->headers[strtolower($name)];
	}

	public function extractMessageHeader() {
		$headers = new NNTPHeader;
		foreach ($this->headers as $name => $header) {
			if (substr($name,0,7) != "content") {
				$headers->set($header);
			}
		}
		return $headers;
	}

	public function extractContentHeader() {
		$headers = new NNTPHeader;
		foreach ($this->headers as $name => $header) {
			if (substr($name,0,7) == "content") {
				$headers->set($header);
			}
		}
		return $headers;
	}

	public function getPlain() {
		$text = "";
		foreach ($this->headers AS $header) {
			$text .= $header->getPlain() . "\r\n";
		}
		return $text;
	}
}

class NNTPSingleHeader {
	public static function parsePlain($line) {
		list($name, $value) = explode(":", $line, 2);

		// Eventuell vorhandene Extra-Attribute auffangen
		$extras = explode(";", $value);
		$value = trim(array_shift($extras));
		$h = new NNTPSingleHeader(trim($name), $value);
		foreach ($extras AS $extra) {
			list($name, $value) = explode("=", $extra, 2);
			$name = trim($name);
			$value = trim(trim($value),'"');
			$h->addExtra($name, $value);
		}
		return $h;
	}

	public static function generate($name, $value, $charset) {
		mb_internal_encoding($charset);
		return new NNTPSingleHeader($name, mb_encode_mimeheader($value, $charset));
	}

	private $name;
	private $value;
	private $extra = array();

	public function __construct($name, $value) {
		$this->name = $name;
		$this->value = $value;
	}

	public function getName() {
		return $this->name;
	}

	public function getValue($charset = null) {
		if ($charset != null) {
			return iconv(mb_internal_encoding(), $charset, mb_decode_mimeheader($this->getValue()));
		}
		return $this->value;
	}

	public function getCharset() {
		return $this->charset;
	}

	public function addExtra($name, $value) {
		$this->extra[strtolower($name)] = $value;
	}

	public function hasExtra($name) {
		return isset($this->extra[strtolower($name)]);
	}

	public function getExtra($name, $charset = null) {
		if ($charset != null) {
			return iconv(mb_internal_encoding(), $charset, mb_decode_mimeheader($this->getValue()));
		}
		return $this->extra[strtolower($name)];
	}

	public function getPlain() {
		$text = $this->getName() . ": " . $this->getValue();
		// Fuege Zusaetzliche Informationen hinzu
		foreach ($this->extra AS $name => $extra) {
			$text .= "; " . $name . "=\"" . addclashes($extra, '"') . "\"";
		}
		return $text;
	}
}

?>
