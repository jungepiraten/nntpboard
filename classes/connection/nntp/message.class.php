<?php

require_once(dirname(__FILE__) . "/header.class.php");
require_once(dirname(__FILE__) . "/address.class.php");
require_once(dirname(__FILE__) . "/mimebody.class.php");

class NNTPMessage {
	public static function parsePlain($group, $artnr, $plain) {
		list($header, $body) = explode("\r\n\r\n", $plain, 2);

		// Header parsen
		$header = NNTPHeader::parsePlain($header);

		// Body parsen
		$body = NNTPMimeBody::parsePlain($header, $body);
		
		$message = new NNTPMessage($group, $artnr, $header, $body, $charset);
		return $message;
	}

	public static function parseObject($message) {
		// TODO header zum objekt erzeugen
		// TODO inhalte zusammensetzen
	}

	private $group;
	private $artnr;
	private $header;
	private $body;
	private $charset;

	public function __construct($group, $artnr, $header, $body, $charset) {
		$this->group = $group;
		$this->artnr = $artnr;
		$this->header = $header;
		$this->body = $body;
		$this->charset = $charset;
	}

	public function getHeader() {
		return $this->header;
	}

	public function getCharset() {
		return $this->charset;
	}

	// TODO getPlain() & getObject() erzeugen
	public function getObject() {
		/* Lese Header */
		$messageid =	$this->getHeader()->get("Message-ID")->getValue();
		$subject =	$this->getHeader()->get("Subject")->getValue();
		$date =		strtotime($this->getHeader()->get("Date")->getValue());
		$author =	NNTPAddress::parsePlain(
					array_shift(explode(",", $this->getHeader()->get("From")->getValue()))
					)->getObject();
		
		$charset = "UTF-8";
		if ($this->getHeader()->has("Content-Type") && $this->getHeader()->get("Content-Type")->hasExtra("charset")) {
			$charset = $this->getHeader()->get("Content-Type")->getExtra("charset");
		}

		/* Thread finden */

		// Default: Neuer Thread
		$parentid = null;

		// References
		if ($this->getHeader()->has("References") && trim($this->getHeader()->get("References")->getValue()) != "") {
			$references = explode(" ", $this->getHeader()->get("References")->getValue());
			$parentid = array_pop($references);
		}

		// Nachrichteninhalt
		$textbody = $this->body->getTextBody($charset);
		$htmlbody = $this->body->getHtmlBody($charset);
		
		$message = new Message($this->group, $this->artnr, $messageid, $date, $author, $subject, $charset, $parentid, $textbody, $htmlbody);
		
		/* Strukturanalyse des Bodys */
		foreach ($this->body->getAttachmentParts() AS $attachment) {
			$message->addAttachment($attachment->getObject());
		}
		
		return $message;
	}
}

?>
