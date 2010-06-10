<?php

require_once(dirname(__FILE__) . "/header.class.php");
require_once(dirname(__FILE__) . "/plainbody.class.php");
require_once(dirname(__FILE__) . "/mimebody/mixed.class.php");
require_once(dirname(__FILE__) . "/mimebody/related.class.php");
require_once(dirname(__FILE__) . "/mimebody/alternative.class.php");
require_once(dirname(__FILE__) . "/mimebody/signed.class.php");

abstract class NNTPMimeBody {
	public static function parsePlain($header, $body) {
		$parts = array();
		$mimetype = null;
		
		if ($header->has("Content-Type")
		 && substr($header->get("Content-Type")->getValue(),0,9) == "multipart"
		 && $header->get("Content-Type")->hasExtra("boundary"))
		{
			// Multipart-Nachricht	
			$mimetype = $header->get("Content-Type")->getValue();
			$boundary = $header->get("Content-Type")->getExtra("boundary");
			$mimeparts = explode("--" . $boundary, $body);
			// Normalerweise bestehen der erste (This is an multipart ...) und letzte Teil (-- nur aus Sinnlosem Inhalt
			// Falls das nicht so ist, fixen wir das halt ...
			if (($last = trim(array_pop($mimeparts))) != "--") {
				array_push($mimeparts, $last);
			}
			array_shift($mimeparts);
			
			foreach ($mimeparts AS $mimepart) {
				list($partheader, $partbody) = explode("\r\n\r\n", $mimepart, 2);
				$partheader = NNTPHeader::parsePlain($partheader);
				$parts[] = self::parsePlain($partheader, $partbody);
			}
		} else {
			// Singlepart-Nachricht
			$parts[] = NNTPPlainBody::parsePlain($header, $body);
		}

		switch ($mimetype) {
		case "multipart/signed":
			return new NNTPSignedMimeBody($header, $parts);
		case "multipart/related":
			return new NNTPRelatedMimeBody($header, $parts);
		case "multipart/alternative":
			return new NNTPAlternativeMimeBody($header, $parts);
		default:
		case "multipart/mixed":
			return new NNTPMixedMimeBody($header, $parts);
		}
	}
	
	public static function parseObject($message) {
		$parts = array();

		// Text-Teil
		if ($message->hasTextBody()) {
			$parts[] = NNTPPlainBody::parse("text/plain", $message->getCharset(), "base64", $message->getTextBody());
		}
		// HTML-Teil
		if ($message->hasHTMLBody()) {
			$parts[] = NNTPPlainBody::parse("text/html", $message->getCharset(), "base64", $message->getHTMLBody());
		}
		// In einen alternative-Body packen (falls noetig)
		if (count($parts) > 1) {
			$header = new NNTPHeader;
			$contenttype = NNTPSingleHeader::generate("Content-Type",	"multipart/alternative",	$message->getCharset());
			$contenttype->setExtra("boundary", "--" . md5(uniqid()));
			$header->set($contenttype);
			$parts = array(new NNTPAlternativeMimeBody($header, $parts));
		}
		// Eventuelle Attachments verpacken wir jetzt
		foreach ($message->getAttachments() AS $attachment) {
			$header = new NNTPHeader;
			$contenttype = NNTPSingleHeader::generate("Content-Type",	"multipart/alternative",	$message->getCharset());
			$contenttype->setExtra("boundary", "--" . md5(uniqid()));
			$header->set($contenttype);
			$parts[] = NNTPPlainBody::parseObject($attachment);
		}
		// ein multipart/mixed lohnt sich wirklich nur, wenn wir auch Attachments haben
		if (count($parts) > 1) {
			return new NNTPMixedMimeBody($header, $parts);
		}
		return array_shift($parts);
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
