<?php

require_once(dirname(__FILE__) . "/header.class.php");

class NNTPMessage {
	public static function parsePlain($plain) {
		list($header, $body) = explode("\r\n\r\n", $article, 2);

		// Header parsen
		$header = NNTPHeader::parseLines($header);

		// Body parsen
		$parts = NNTPBody::parsePlain($header, $body);

		// TODO parts durchgehen und parsen
		
		$message = new NNTPMessage($header, $parts, $charset);
	}

	public static function parseObject($message) {
		// TODO header zum objekt erzeugen
		// TODO inhalte zusammensetzen
	}

	private $header;
	private $parts;
	private $charset;

	public function __construct($header, $parts, $charset) {
		$this->header = $header;
		$this->parts = $parts;
		$this->charset = $charset;
	}

	public function getCharset() {
		return $this->charset;
	}

	// TODO getPlain() & getObject() erzeugen
}

?>
