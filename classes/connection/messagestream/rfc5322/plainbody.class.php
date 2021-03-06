<?php

require_once(dirname(__FILE__) . "/header.class.php");
require_once(dirname(__FILE__) . "/../../../attachment.class.php");

if (!function_exists("quoted_printable_encode")) {
	// aus http://de.php.net/quoted_printable_decode
	function quoted_printable_encode($string) {
		$string = str_replace(array('%20', '%0D%0A', '%'), array(' ', "\r\n", '='), rawurlencode($string));
		$string = preg_replace('/[^\r\n]{73}[^=\r\n]{2}/', "$0=\r\n", $string);
		return $string;
	}
}

class RFC5322PlainBody {
	public static function parsePlain($header, $body) {
		return new RFC5322PlainBody($header, $body);
	}

	public static function parseObject($attachment) {
		$header = new RFC5322Header;
		$header->setValue("Content-Type",			$attachment->getMimeType() );
		$header->setValue("Content-Disposition",		$attachment->getDisposition() );
		if ($attachment->hasFilename()) {
			$header->get("Content-Disposition")->addExtra("filename", $attachment->getFilename());
		}

		if ($attachment->isBinary()) {
			$header->setValue("Content-Transfer-Encoding",	"base64");
			$content = chunk_split(base64_encode($attachment->getContent()), 76, "\r\n");
		} else {
			$header->get("Content-Type")->addExtra("charset", "UTF-8");
			$header->setValue("Content-Transfer-Encoding",	"quoted-printable");
			$content = quoted_printable_encode($attachment->getContent());
		}

		return new RFC5322PlainBody($header, $content);
	}

	public static function parse($mimetype, $encoding, $body, $charset = "UTF-8") {
		$header = new RFC5322Header;
		$header->setValue("Content-Type",		$mimetype);
		$header->get("Content-Type")->addExtra("charset", $charset);
		$header->setValue("Content-Transfer-Encoding",	$encoding);

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
		return new RFC5322PlainBody($header, $body);
	}

	private $header;
	private $body;

	public function __construct($header, $body) {
		$this->header = $header;
		$this->body = $body;
	}

	public function getHeader() {
		return $this->header;
	}

	public function getCharset() {
		if ($this->getHeader()->has("Content-Type")
		 && $this->getHeader()->get("Content-Type")->hasExtra("charset")) {
			return $this->getHeader()->get("Content-Type")->getExtra("charset");
		}
		return null;
	}

	public function getMimeType() {
		if ($this->getHeader()->has("Content-Type")) {
			return strtolower($this->getHeader()->get("Content-Type")->getValue());
		}
		return "text/plain";
	}

	public function getTransferEncoding() {
		// See RFC 2045 / Section 6.1.
		if ($this->getHeader()->has("Content-Transfer-Encoding")) {
			return strtolower($this->getHeader()->get("Content-Transfer-Encoding")->getValue());
		}
		return "7bit";
	}

	public function getDisposition() {
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
		if ($this->getHeader()->has("Content-Type")
		 && $this->getHeader()->get("Content-Type")->hasExtra("name")) {
			return $this->getHeader()->get("Content-Type")->getExtra("name");
		}
		return null;
	}

	public function getBody($charset = "UTF-8") {
		$body = $this->body;

		if ($this->getTransferEncoding() == "quoted-printable") {
			$body = quoted_printable_decode($body);
		} elseif ($this->getTransferEncoding() == "base64") {
			$body = base64_decode($body);
		}

		if ($this->getCharset() != null) {
			$body = iconv($this->getCharset(), $charset, $body);
		}
		return $body;
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
