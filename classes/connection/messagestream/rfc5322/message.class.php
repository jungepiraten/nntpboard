<?php

require_once(dirname(__FILE__) . "/header.class.php");
require_once(dirname(__FILE__) . "/address.class.php");
require_once(dirname(__FILE__) . "/mimebody.class.php");
require_once(dirname(__FILE__) . "/../../../message.class.php");
require_once(dirname(__FILE__) . "/../../../acknowledge.class.php");

class RFC5322Message {
	public static function parsePlain($plain) {
		list($header, $body) = explode("\r\n\r\n", $plain, 2);

		// Header parsen
		$header = RFC5322Header::parsePlain($header);

		// Body parsen
		$body = RFC5322MimeBody::parsePlain($header->extractContentHeader(), $body);
		
		return new RFC5322Message($header->extractMessageHeader(), $body);
	}

	public static function generateReferences($connection, $message) {
		// TODO die letzten 5 messageids hier
		return $message->getParentID();
	}

	public static function parseObject($connection, $group, $message) {
		$charset = $message->getCharset();
		
		$header = new RFC5322Header;
		$header->set(	RFC5322SingleHeader::generate("Message-ID",	$message->getMessageID(), $charset));
		$header->set(	RFC5322SingleHeader::generate("Newsgroups",	$group, $charset));
		if ($message->hasParent()) {
			$header->set(	RFC5322SingleHeader::generate("References",	self::generateReferences($connection, $message), $charset));
		}
		$header->set(	RFC5322SingleHeader::generate("From",
				RFC5322Address::parseObject($message->getAuthor())->getPlain(), $charset));
		$header->set(	RFC5322SingleHeader::generate("Subject",		$message->getSubject(), $charset));
		$header->set(	RFC5322SingleHeader::generate("Date",
				date("r", $message->getDate()), $charset));

		return new RFC5322Message($header, RFC5322MimeBody::parseObject($message));
	}

	public static function parseAcknowledgeObject($connection, $group, $ack, $message) {
		$charset = $message->getCharset();
		$references = self::generateReferences($connection, $message);
		$references .= " " . $message->getMessageID();

		$header = new RFC5322Header;
		$header->set(	RFC5322SingleHeader::generate("Message-ID",	$ack->getMessageID(), $charset));
		$header->set(	RFC5322SingleHeader::generate("Newsgroups",	$group, $charset));
		$header->set(	RFC5322SingleHeader::generate("X-Acknowledge",	($ack->getWertung() >= 0 ? "+" : "-") . abs(intval($ack->getWertung())), $charset));
		$header->set(	RFC5322SingleHeader::generate("References",	$references, $charset));
		$header->set(	RFC5322SingleHeader::generate("From",
				RFC5322Address::parseObject($ack->getAuthor())->getPlain(), $charset));
		$header->set(	RFC5322SingleHeader::generate("Subject",		"[" . ($ack->getWertung() >= 0 ? "+" : "-") . abs(intval($ack->getWertung())) . "] " . $message->getSubject(), $charset));
		$header->set(	RFC5322SingleHeader::generate("Date",
				date("r", $ack->getDate()), $charset));

		return new RFC5322Message($header, RFC5322MimeBody::parseAcknowledgeObject($ack, $message));
	}

	public static function parseCancelObject($connection, $group, $cancel, $message) {
		$charset = $message->getCharset();

		$header = new RFC5322Header;
		$header->set(	RFC5322SingleHeader::generate("Message-ID",	$cancel->getMessageID(), $charset));
		$header->set(	RFC5322SingleHeader::generate("Newsgroups",	$group, $charset));
		$header->set(	RFC5322SingleHeader::generate("From",
				RFC5322Address::parseObject($cancel->getAuthor())->getPlain(), $charset));
		$header->set(	RFC5322SingleHeader::generate("Subject",		"[CANCEL] " . $message->getSubject(), $charset));
		$header->set(	RFC5322SingleHeader::generate("Control",		"cancel " . $cancel->getReference(), $charset));
		$header->set(	RFC5322SingleHeader::generate("Date",
				date("r", $cancel->getDate()), $charset));

		return new RFC5322Message($header, RFC5322MimeBody::parseCancelObject($cancel, $message));
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

	public function isAcknowledge() {
		return preg_match('~^[+-][0-9]{1,4}~', $this->body->getBodyPart("text/plain","UTF-8"));
	}

	public function getPlain() {
		// Nur einen Zeilenumbruch, damit der Body auch noch Content-Header hinzufuegen kann
		$text  = rtrim($this->header->getPlain()) . "\r\n";
		$text .= $this->body->getPlain();
		return $text;
	}

	public function getObject($connection) {
		// Diktatorisch beschlossen :P
		$charset = "UTF-8";
		
		// Header interpretieren
		$messageid =	$this->getHeader()->get("Message-ID")->getValue($charset);
		$subject =	$this->getHeader()->get("Subject")->getValue($charset);
		$date =		strtotime($this->getHeader()->get("Date")->getValue($charset));
		// Bei "Mailman" benutzen wir lieber die Mailadresse, weil Mailingliste
		if ($this->getHeader()->has("Sender")
		 && (strtolower($this->getHeader()->get("Sender")->getValue("UTF-8")) != "mailman@community.junge-piraten.de")) {
			$author =	RFC5322Address::parsePlain($this->getHeader()->get("Sender")->getValue($charset))->getObject();
		} else {
			// TODO was machen bei mehreren From-Adressen (per RFC erlaubt!)
			$author =	RFC5322Address::parsePlain(
						array_shift(explode(",", $this->getHeader()->get("From")->getValue($charset))), $charset
						)->getObject();
		}

		// References (per Default als neuer Thread)
		$parentid = null;
		if ($this->getHeader()->has("References") && trim($this->getHeader()->get("References")->getValue($charset)) != "") {
			$references = preg_split('$\\s$', $this->getHeader()->get("References")->getValue($charset));
			do {
				$parentid = array_pop($references);
			} while ($parentid != false && !$connection->hasMessage($parentid));
		}

		// Fiese Fixes gegen dumme Clients, die kein References setzen
		if ($parentid == null && strtolower(substr($subject,0,3)) == "re:") {
			$topicsubject = ltrim(substr($subject,3));
			// TODO Themen nach Subject suchen und MessageID raussuchen
		}

		try {
			$message = $connection->getMessage($parentid);
			while ($message instanceof Acknowledge) {
				$parentid = $message->getReference();
				$message = $connection->getMessage($parentid);
			}
		} catch (NotFoundMessageException $e) {}

		if ($this->isAcknowledge()) {
			preg_match('~^[+-][0-9]{1,4}~', $this->body->getBodyPart("text/plain","UTF-8"), $match);
			return new Acknowledge($messageid, $parentid, $date, $author, intval($match[0]) );
		}

		// Nachrichteninhalt
		$textbodys = explode("\n-- ", $this->body->getBodyPart("text/plain", $charset), 2);
		$signature = null;
		if (count($textbodys) >= 2) {
			$signature = array_pop($textbodys);
		}
		$textbody = implode("\n-- ", $textbodys);
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
