<?php

require_once(dirname(__FILE__) . "/header.class.php");
require_once(dirname(__FILE__) . "/plainbody.class.php");

class NNTPMimeBody {
	public static function parsePlain($header, $body) {
		$parts = array();
		// Wir benutzen multipart/mixed auch fuer Nachrichten mit nur einem Teil
		$mimetype = "multipart/mixed";
		// TODO abfrage erweitern ...
		if ($header->has("Content-Type") && $header->get("Content-Type")->hasExtra("boundary")) {
			$mimetype = $header->get("Content-Type")->getValue();
			$mimeparts = explode("--" . $header->get("Content-Type")->getExtra("boundary"), $body);
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
		return new NNTPMimeBody($mimetype, $parts);
	}
	
	private $mimetype;
	private $parts;

	public function __construct($mimetype, $parts) {
		$this->mimetype = $mimetype;
		$this->parts = $parts;
	}

	public function getMimeType() {
		return strtolower($this->mimetype);
	}

	public function getTextBody($charset = null) {
		// TODO diverse multipart/* -typen beruecksichtigen
		$text = "";
		foreach ($this->parts as $part) {
			if ($part instanceof NNTPMimeBody) {
				$text .= $part->getTextBody($charset);
			} else if ($part->getMimeType() == "text/plain") {
				$text .= $part->getBody($charset);
			}
		}
		return $text;
	}

	public function getHTMLBody($charset = null) {
		// TODO diverse multipart/* -typen beruecksichtigen
		$text = "";
		foreach ($this->parts as $part) {
			if ($part instanceof NNTPMimeBody) {
				$text .= $part->getHTMLBody($charset);
			} else if ($part->getMimeType() == "text/html") {
				$text .= $part->getBody($charset);
			}
		}
		return $text;
	}

	public function getAttachmentParts() {
		// TODO diverse multipart/* -typen beruecksichtigen
		$attachments = array();
		foreach ($this->parts as $part) {
			// TODO besserer attachment-check
			if ($part instanceof NNTPMimeBody) {
				$attachments = array_merge($attachments, $part->getAttachmentParts());
			} else if (!preg_match("#^(text/.*)$#", $part->getMimeType())) {
				$attachments[] = $part;
			}
		}
		return $attachments;
	}
}

?>
