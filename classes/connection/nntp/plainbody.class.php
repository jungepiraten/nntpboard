<?php

require_once(dirname(__FILE__) . "/header.class.php");
require_once(dirname(__FILE__) . "/../../attachment.class.php");

class NNTPPlainBody {
	public static function parsePlain($header, $body) {
		// Per default nehmen wir UTF-8 (warum auch was anderes?)
		$charset = "UTF-8";
		if ($header->has("Content-Type") && $header->get("Content-Type")->hasExtra("charset")) {
			$charset = $header->get("Content-Type")->getExtra("charset");
		}
		
		/** See RFC 2045 / Section 6.1. **/
		$encoding = "7bit";
		if ($header->has("Content-Transfer-Encoding")) {
			$encoding = strtolower($header->get("Content-Transfer-Encoding")->getValue());
		}
		switch ($encoding) {
		default:
		case "7bit":
		case "8bit":
		case "binary":
			// No encoding. => Do nothing.
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
		if ($header->has("Content-Type")) {
			$mimetype = $header->get("Content-Type")->getValue();
		}

		/** Disposition **/
		$disposition = "inline";
		$filename = null;
		if ($header->has("Content-Disposition")) {
			$disposition = $header->get("Content-Disposition")->getValue();
			if ($header->get("Content-Disposition")->hasExtra("filename")) {
				$filename = $header->get("Content-Disposition")->getExtra("filename");
			}
		}

		return new NNTPPlainBody($body, $mimetype, $disposition, $filename, $charset);
	}

	private $body;
	private $mimetype;
	private $disposition;
	private $filename;
	private $charset;

	public function __construct($body, $mimetype, $disposition, $filename, $charset) {
		$this->charset = $charset;
		$this->mimetype = $mimetype;
		$this->disposition = $disposition;
		$this->filename = $filename;
		$this->body = $body;
	}

	public function getCharset() {
		return $this->charset;
	}

	public function getBody($charset = null) {
		if ($charset != null) {
			return iconv($this->getCharset(), $charset, $this->getBody());
		}
		return $this->body;
	}

	public function getMimeType() {
		return strtolower($this->mimetype);
	}

	public function getDisposition() {
		return $this->disposition;
	}

	public function getFileName() {
		return $this->filename;
	}

	public function getObject() {
		return new Attachment($this->getDisposition(), $this->getMimeType(), $this->getBody(), $this->getCharset(), $this->getFileName());
	}
}

?>
