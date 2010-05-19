<?php

require_once(dirname(__FILE__) . "/header.class.php");
require_once(dirname(__FILE__) . "/../../attachment.class.php");

if (!function_exists("quoted_printable_encode")) {
	// aus http://de.php.net/quoted_printable_decode
	function quoted_printable_encode($string) {
		$string = str_replace(array('%20', '%0D%0A', '%'), array(' ', "\r\n", '='), rawurlencode($string));
		$string = preg_replace('/[^\r\n]{73}[^=\r\n]{2}/', "$0=\r\n", $string);
		return $string;
	}
}

class NNTPPlainBody {
	public static function parsePlain($header, $body) {
		return new NNTPPlainBody($header, $body);
	}

	public static function parseObject($attachment) {
		$header = new NNTPHeader;
		$header->set(	new NNTPSingleHeader("Content-Type",			$attachment->getMimeType()));
		$header->set(	new NNTPSingleHeader("Content-Transfer-Encoding",	"base64"));
		$header_disposition = new NNTPSingleHeader("Content-Disposition",	$attachment->getDisposition());
		if ($attachment->hasFilename()) {
			$header_disposition->addExtra("filename", $attachment->getFilename());
		}
		$header->set($header_disposition);
		return new NNTPPlainBody($header, base64_encode($attachment->getContent()));
	}

	public static function parse($mimetype, $charset, $encoding, $body) {
		$header = new NNTPHeader;
		$contenttypeheader = new NNTPSingleHeader("Content-Type",		$mimetype);
		$contenttypeheader->addExtra("charset", $charset);
		$header->set($contenttypeheader);
		$header->set(	new NNTPSingleHeader("Content-Transfer-Encoding",	$encoding));

		switch ($encoding) {
		case "7bit":
		case "8bit":
		case "binary":
			// Keine weitere Kodierung gewuenscht!
			break;
		case "base64":
			$body = chunk_split(base64_encode($body), 76, "\r\n");
			break;
		case "quoted-printable":
			$body = quoted_printable_encode($body);
			break;
		}
		return new NNTPPlainBody($header, $body);
	}

	private $header;
	private $body;

	public function __construct($header, $body) {
		$this->header = $header;
		$this->body = $body;
	}

	private function getHeader() {
		return $this->header;
	}

	private function getCharset() {
		if ($this->getHeader()->has("Content-Type") && $this->getHeader()->get("Content-Type")->hasExtra("charset")) {
			return $this->getHeader()->get("Content-Type")->getExtra("charset");
		}
		return "UTF-8";
	}

	public function getMimeType() {
		if ($this->getHeader()->has("Content-Type")) {
			return strtolower($this->getHeader()->get("Content-Type")->getValue());
		}
		return "text/plain";
	}

	private function getTransferEncoding() {
		// See RFC 2045 / Section 6.1.
		if ($this->getHeader()->has("Content-Transfer-Encoding")) {
			return strtolower($this->getHeader()->get("Content-Transfer-Encoding")->getValue());
		}
		return "7bit";
	}

	private function getDisposition() {
		if ($this->getHeader()->has("Content-Disposition")) {
			return $this->getHeader()->get("Content-Disposition")->getValue();
		}
		return "inline";
	}

	private function getFileName() {
		if ($this->getHeader()->has("Content-Disposition")
		 && $this->getHeader()->get("Content-Disposition")->hasExtra("filename")) {
			return $this->getHeader()->get("Content-Disposition")->getExtra("filename");
		}
		return null;
	}

	public function getBody($charset = null) {
		if ($charset != null) {
			return iconv($this->getCharset(), $charset, $this->getBody());
		}
		switch ($this->getTransferEncoding()) {
		default:
		case "7bit":
		case "8bit":
		case "binary":
			return $this->body;
			break;
		case "quoted-printable":
			return quoted_printable_decode($this->body);
			break;
		case "base64":
			return base64_decode($this->body);
			break;
		}
	}

	public function getPlain() {
		$text  = rtrim($this->getHeader()->getPlain()) . "\r\n\r\n";
		$text .= $this->body;
		return $text;
	}

	public function getObject() {
		// Attachments haben keinen Zeichensatz, da wir nur Binaere Inhalte nutzen (hoffentlich *g*)
		return new Attachment($this->getDisposition(), $this->getMimeType(), $this->getBody(), $this->getFileName());
	}
}

?>
