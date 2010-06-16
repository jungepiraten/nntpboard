<?php

require_once(dirname(__FILE__) . "/header.class.php");
require_once(dirname(__FILE__) . "/address.class.php");
require_once(dirname(__FILE__) . "/mimebody.class.php");

class NNTPMessage {
	public static function parsePlain($plain) {
		list($header, $body) = explode("\r\n\r\n", $plain, 2);

		// Header parsen
		$header = NNTPHeader::parsePlain($header);

		// Body parsen
		$body = NNTPMimeBody::parsePlain($header->extractContentHeader(), $body);
		
		return new NNTPMessage($header->extractMessageHeader(), $body);
	}

	public static function parseObject($group, $message) {
		$charset = $message->getCharset();
		
		$header = new NNTPHeader;
		$header->set(	NNTPSingleHeader::generate("Message-ID",	$message->getMessageID(), $charset));
		$header->set(	NNTPSingleHeader::generate("Newsgroups",	$group, $charset));
		if ($message->hasParent()) {
			$header->set(	NNTPSingleHeader::generate("References",	$message->getParentID(), $charset));
		}
		$header->set(	NNTPSingleHeader::generate("From",
				NNTPAddress::parseObject($message->getAuthor())->getPlain(), $charset));
		$header->set(	NNTPSingleHeader::generate("Subject",		$message->getSubject(), $charset));
		$header->set(	NNTPSingleHeader::generate("Date",
				date("r", $message->getDate()), $charset));

		return new NNTPMessage($header, NNTPMimeBody::parseObject($message));
	}

	private $header;
	private $body;

	public function __construct($header, $body) {
		$this->header = $header;
		$this->body = $body;
	}

	public function getCharset() {
		return $this->charset;
	}

	private function getHeader() {
		return $this->header;
	}

	public function getPlain() {
		// Nur einen Zeilenumbruch, damit der Body auch noch Content-Header hinzufuegen kann
		$text  = rtrim($this->header->getPlain()) . "\r\n";
		$text .= $this->body->getPlain();
		return $text;
	}

	public function getObject() {
		// Diktatorisch beschlossen :P
		$charset = "UTF-8";
		
		// Header interpretieren
		$messageid =	$this->getHeader()->get("Message-ID")->getValue($charset);
		$subject =	$this->getHeader()->get("Subject")->getValue($charset);
		$date =		strtotime($this->getHeader()->get("Date")->getValue($charset));
		if ($this->getHeader()->has("Sender")) {
			$author =	NNTPAddress::parsePlain($this->getHeader()->get("Sender")->getValue($charset))->getObject();
		} else {
			// TODO was machen bei mehreren From-Adressen (per RFC erlaubt!)
			$author =	NNTPAddress::parsePlain(
						array_shift(explode(",", $this->getHeader()->get("From")->getValue($charset))), $charset
						)->getObject();
		}

		// References (per Default als neuer Thread)
		$parentid = null;
		if ($this->getHeader()->has("References") && trim($this->getHeader()->get("References")->getValue($charset)) != "") {
			$references = explode(" ", $this->getHeader()->get("References")->getValue($charset));
			$parentid = array_pop($references);
		}

		// Nachrichteninhalt
		$textbodys = explode("\n--", $this->body->getBodyPart("text/plain", $charset), 2);
		$signature = null;
		if (count($textbodys) >= 2) {
			$signature = array_pop($textbodys);
		}
		$textbody = implode("\n--", $textbodys);
		$htmlbody = $this->body->getBodyPart("text/html", $charset);
		
		$message = new Message($messageid, $date, $author, $subject, $charset, $parentid, $textbody, $signature, $htmlbody);
		
		/* Strukturanalyse des Bodys */
		foreach ($this->body->getAttachmentParts() AS $attachment) {
			$message->addAttachment($attachment->getObject());
		}
		
		return $message;
	}
}

?>
