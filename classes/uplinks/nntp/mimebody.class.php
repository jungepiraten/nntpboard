<?php

require_once(dirname(__FILE__) . "/header.class.php");
require_once(dirname(__FILE__) . "/plainbody.class.php");
require_once(dirname(__FILE__) . "/mimebody/mixed.class.php");
require_once(dirname(__FILE__) . "/mimebody/alternative.class.php");
require_once(dirname(__FILE__) . "/mimebody/signed.class.php");

if (!function_exists("quoted_printable_encode")) {
	// aus http://de.php.net/quoted_printable_decode
	function quoted_printable_encode($string) {
		$string = str_replace(array('%20', '%0D%0A', '%'), array(' ', "\r\n", '='), rawurlencode($string));
		$string = preg_replace('/[^\r\n]{73}[^=\r\n]{2}/', "$0=\r\n", $string);
		return $string;
	}
}

abstract class NNTPMimeBody {
	public static function parsePlain($header, $body) {
		$parts = array();
		$mimetype = null;
		// TODO abfrage erweitern ...
		if ($header->has("Content-Type") && $header->get("Content-Type")->hasExtra("boundary")) {
			$mimetype = $header->get("Content-Type")->getValue();
			$boundary = $header->get("Content-Type")->getExtra("boundary");
			$mimeparts = explode("--" . $boundary, $body);
			// Der erste (This is an multipart ...) und letzte Teil (--) besteht nur aus Sinnlosem Inhalt
			array_pop($mimeparts);
			array_shift($mimeparts);
			
			foreach ($mimeparts AS $mimepart) {
				list($partheader, $partbody) = explode("\r\n\r\n", $mimepart, 2);
				$partheader = NNTPHeader::parsePlain($partheader);
				$parts[] = self::parsePlain($partheader, $partbody);
			}
		} else {
			$parts[] = NNTPPlainBody::parsePlain($header, $body);
		}
		
		switch ($mimetype) {
		case "multipart/signed":
			return new NNTPSignedMimeBody($header, $parts);
		case "multipart/alternative":
			return new NNTPAlternativeMimeBody($header, $parts);
		default:
		case "multipart/mixed":
			return new NNTPMixedMimeBody($header, $parts);
		}
	}
	
	public static function parseObject($message) {
		// TODO breitere unterstuetzung als nur text/plain
		$header = new NNTPHeader;
		$header->set(	NNTPSingleHeader::generate("Content-Type",	"text/plain",	$message->getCharset()));
		$parts = array(new NNTPPlainBody($header, $message->getTextBody()));
		return new NNTPMixedMimeBody($header, $parts);
	}
	
	private $header;
	private $parts;

	public function __construct($header, $parts) {
		$this->header = $header;
		$this->parts = $parts;
	}

	private function getHeader() {
		return $this->header;
	}

	public function getMimeType() {
		if ($this->getHeader()->has("Content-Type")
		 && substr(strtolower($this->getHeader()->get("Content-Type")->getValue()), 0, 9) == "multipart") {
			return strtolower($this->getHeader()->get("Content-Type")->getValue());
		}
		return null;
	}

	private function getBoundary() {
		if ($this->getHeader()->has("Content-Type")
		 && $header->get("Content-Type")->hasExtra("boundary")) {
			return $header->get("Content-Type")->getExtra("boundary");
		}
		return null;
	}

	protected function getParts() {
		return $this->parts;
	}

	abstract public function getBodyPart($mimetype, $charset = null);
	abstract public function getAttachmentParts();

	public function getPlain() {
		if ($this->getMimeType() == null) {
			return reset($this->parts)->getPlain();
		}
		$text  = rtrim($this->getHeader()->getValue()) . "\r\n\r\n";
		foreach ($this->parts AS $part) {
			$text .= "--" . $this->getBoundary() . "\r\n";
			$text .= rtrim($part->getPlain()) . "\r\n\r\n";
		}
		$text .= "--" . $this->getBoundary() . "--\r\n";
		return $text;
	}
}

?>
