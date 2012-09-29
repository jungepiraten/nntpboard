<?php

class RFC5322Header {
	public static function parsePlain($plain) {
		if (!is_array($plain)) {
			$plain = explode("\r\n", $plain);
		}
		$header = new RFC5322Header;
		for ($i=0; $i<count($plain); $i++) {
			// Eventuellen Zeilenruecklauf abschneiden
			$line = rtrim($plain[$i]);
			// Multiline-Header
			while (isset($plain[$i+1]) && preg_match("$^\s$", $plain[$i+1])) {
				$line .= " " . ltrim($plain[++$i]);
			}
			if (!empty($line)) {
				$header->set(RFC5322SingleHeader::parsePlain($line));
			}
		}
		return $header;
	}

	private $headers = array();

	public function __construct() {
	}

	public function setValue($header, $value, $charset = "UTF-8") {
		$this->set(RFC5322SingleHeader::generate($header, $value, $charset));
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
		$headers = new RFC5322Header;
		foreach ($this->headers as $name => $header) {
			if (substr($name,0,7) != "content") {
				$headers->set($header);
			}
		}
		return $headers;
	}

	public function extractContentHeader() {
		$headers = new RFC5322Header;
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

class RFC5322SingleHeader {
	public static function parsePlain($line) {
		list($name, $value) = explode(":", $line, 2);

		// Eventuell vorhandene Extra-Attribute auffangen
		$extras = explode(";", $value);
		$value = trim(array_shift($extras));
		$h = new RFC5322SingleHeader(trim($name), $value);
		foreach ($extras AS $extra) {
			// Fixe vermeindliche Extra-Header (z.b. Bei User-Agent)
			if (strpos($extra, "=") === false) {
				$value .= ";".$extra;
				continue;
			}
			list($name, $value) = explode("=", $extra, 2);
			$name = trim($name);
			$value = trim(trim($value),'"');
			$h->addExtra($name, $value);
		}
		return $h;
	}

	public static function generate($name, $value, $charset = "UTF-8") {
		// Nur encoden wenn noetig
		if (preg_match_all('/(\w*[\x80-\xFF]+\w*)/', $value, $matches)) {
			mb_internal_encoding($charset);
			$value = mb_encode_mimeheader($value, $charset, "B");
		}
		return new RFC5322SingleHeader($name, $value);
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

	public function getValue($charset = "UTF-8") {
		$value = $this->value;
		preg_match_all('$\\s?=\\?(.*?)\\?([bBqQ])\\?(.*?)\\?=$', $value, $parts, PREG_SET_ORDER);
		foreach ($parts as $part) {
			$decoded = ltrim($part[0]);
			if (strtolower($part[2]) == "q") {
				$decoded = str_replace("_", " ", $decoded);
			}
			$decoded = iconv(mb_internal_encoding(), $charset, mb_decode_mimeheader($decoded));
			$value = str_replace($part[0], $decoded, $value);
		}
		return $value;
	}

	public function addExtra($name, $value) {
		$this->extra[strtolower($name)] = $value;
	}

	public function hasExtra($name) {
		return isset($this->extra[strtolower($name)]);
	}

	public function getExtra($name, $charset = "UTF-8") {
		$value = $this->extra[strtolower($name)];
		preg_match_all('$\\s?=\\?(.*?)\\?([bBqQ])\\?(.*?)\\?=$', $value, $parts, PREG_SET_ORDER);
		foreach ($parts as $part) {
			$decoded = ltrim($part[0]);
			if (strtolower($part[2]) == "q") {
				$decoded = str_replace("_", " ", $decoded);
			}
			$decoded = iconv(mb_internal_encoding(), $charset, mb_decode_mimeheader($decoded));
			$value = str_replace($part[0], $decoded, $value);
		}
		return $value;
	}

	public function getPlain() {
		$text = $this->name . ": " . $this->value;
		// Fuege Zusaetzliche Informationen hinzu
		foreach ($this->extra AS $name => $extra) {
			$text .= "; " . $name . "=\"" . addcslashes($extra, '"') . "\"";
		}
		return $text;
	}
}

?>
