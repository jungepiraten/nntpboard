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
}

class NNTPSingleHeader {
	public static function parsePlain($line) {
		list($name, $value) = explode(":", $line, 2);

		// Eventuell vorhandene Extra-Attribute auffangen
		$extras = explode(";", $value);
		$value = trim(array_shift($extras));
		$h = new NNTPSingleHeader(trim($name), mb_decode_mimeheader($value), mb_internal_encoding());
		foreach ($extras AS $extra) {
			list($name, $value) = explode("=", $extra, 2);
			$name = mb_decode_mimeheader(trim($name));
			$value = mb_decode_mimeheader(trim(trim($value),'"'));
			$h->addExtra($name, $value);
		}
		return $h;
	}

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

	public function getValue($charset = null) {
		if ($charset != null) {
			return iconv($this->getCharset(), $charset, $this->getValue());
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
			return iconv($this->getCharset(), $charset, $this->getExtra($name));
		}
		return $this->extra[strtolower($name)];
	}
}

?>
