<?php

require_once(dirname(__FILE__) . "/header.class.php");

class NNTPBody {
	public static function parsePlain($header, $body) {
		$parts = array();
		
		// TODO abfrage erweitern ...
		if ($header["content-type"]->hasExtra("boundary")) {
			$parts = explode("--" . $header["content-type"]->getExtra("boundary"), $body);
			// Der erste (This is an multipart ...) und letzte Teil (--) besteht nur aus Sinnlosem Inhalt
			array_pop($parts);
			array_shift($parts);
			
			foreach ($parts AS $part) {
				list($partheader, $partbody) = explode("\r\n", $part, 2);
				$partheader = NNTPHeader::parseLines($partheader);
				$parts = array_merge($parts, self::parsePlain($partheader, $partbody));
			}
		} else {
			// Per default nehmen wir UTF-8 (warum auch was anderes?)
			$charset = "UTF-8";
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

				// No encoding => Do nothing
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

			$parts = array(new NNTPBody($body, $mimetype, $disposition, $charset));
		}
		return $parts;
	}

	private $body;
	private $mimetype;
	private $disposition;
	private $charset;

	public function __construct($body, $mimetype, $disposition, $charset) {
		$this->charset = $charset;
		$this->mimetype = $mimetype;
		$this->disposition = $disposition;
		$this->body = $body;
	}

	public function getCharset() {
		return $this->charset;
	}

	public function getBody() {
		return $this->body;
	}

	public function getMimeType() {
		return $this->mimetype;
	}

	public function getDisposition() {
		return $this->disposition;
	}
}

?>
