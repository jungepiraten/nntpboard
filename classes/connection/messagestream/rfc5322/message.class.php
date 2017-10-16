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

	public static function parseObject($connection, $message) {
		$header = new RFC5322Header;
		$header->setValue("Message-ID",		$message->getMessageID() );
		$header->setValue("From", 		RFC5322Address::parseObject($message->getAuthor())->getPlain() );
		$header->setValue("Date",		date("r", $message->getDate()) );
		$header->setValue("Subject",		$message->getSubject() );
		if ($message->hasParent()) {
			$header->setValue("References", self::generateReferences($connection, $message) );
		}
		$header->setValue("User-Agent",		"NNTPBoard <https://github.com/jungepiraten/nntpboard>");

		return new RFC5322Message($header, RFC5322MimeBody::parseObject($message));
	}

	public static function parseAcknowledgeObject($connection, $ack, $message) {
		$references = self::generateReferences($connection, $message);
		$references .= " " . $message->getMessageID();

		$header = new RFC5322Header;
		$header->setValue("Message-ID",		$ack->getMessageID() );
		$header->setValue("From",		RFC5322Address::parseObject($ack->getAuthor())->getPlain() );
		$header->setValue("Date",		date("r", $ack->getDate()) );
		$header->setValue("Subject",		"[" . ($ack->getWertung() >= 0 ? "+" : "-") . abs(intval($ack->getWertung())) . "] " . $message->getSubject() );
		$header->setValue("References",		$references );
		$header->setValue("X-Acknowledge",	($ack->getWertung() >= 0 ? "+" : "-") . abs(intval($ack->getWertung())) );
		$header->setValue("User-Agent",		"NNTPBoard <https://github.com/jungepiraten/nntpboard>");

		return new RFC5322Message($header, RFC5322MimeBody::parseAcknowledgeObject($ack, $message));
	}

	public static function parseCancelObject($connection, $cancel, $message) {
		$header = new RFC5322Header;
		$header->setValue("Message-ID",		$cancel->getMessageID() );
		$header->setValue("From",		RFC5322Address::parseObject($cancel->getAuthor())->getPlain() );
		$header->setValue("Date",		date("r", $cancel->getDate()) );
		$header->setValue("Subject",		"[CANCEL] " . $message->getSubject() );
		$header->setValue("Control",		"cancel " . $cancel->getReference() );
		$header->setValue("User-Agent",		"NNTPBoard <https://github.com/jungepiraten/nntpboard>");

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

	public function getHeader() {
		return $this->header;
	}

	public function isAcknowledge() {
		return preg_match('~^[+-][0-9]{1,4}~', $this->body->getBodyPart("text/plain"));
	}

	public function getPlain() {
		// Nur einen Zeilenumbruch, damit der Body auch noch Content-Header hinzufuegen kann
		$text  = rtrim($this->header->getPlain()) . "\r\n";
		$text .= $this->body->getPlain();
		return $text;
	}

	public function getObject($messageid, $connection) {
		// Header interpretieren
		$subject = $this->getHeader()->has("Subject") ? $this->getHeader()->get("Subject")->getValue() : "";
		$date =	$this->getHeader()->has("Date") ? strtotime($this->getHeader()->get("Date")->getValue()) : time();
		// TODO was machen bei mehreren From-Adressen (per RFC erlaubt!)
		$from_addresses = explode(",", $this->getHeader()->has("From") ? $this->getHeader()->get("From")->getValue() : "unknown");
		$author = RFC5322Address::parsePlain(array_shift($from_addresses))->getObject();

		// References (per Default als neuer Thread)
		$parentid = null;
		if ($this->getHeader()->has("References") && trim($this->getHeader()->get("References")->getValue()) != "") {
			$references = preg_split('$\\s$', $this->getHeader()->get("References")->getValue());
			do {
				$parentid = array_pop($references);
			} while (!( $parentid == false || ($parentid != $messageid && $connection->hasMessage($parentid)) ));
			// do { ... } while (x) is the same as do { ... } until (!x)
		}

		// Fiese Fixes gegen dumme Clients, die kein References setzen
		if ($parentid == null && strtolower(substr($subject,0,3)) == "re:") {
			$topicsubject = ltrim(substr($subject,3));
			// TODO Themen nach Subject suchen und MessageID raussuchen
		}

		// Suche Letzte Nachricht im References-Baum die keine Zustimmung war (quasi die Ursprungsreferenz)
		// Damit vermeiden wir, das die parentid auf ein Acknowledge zeigt und nicht auf ein Message-Objekt
		// Hierbei muss stark aufgepasst werden, das keine Entlosschleife entstehen kann
		try {
			$message = $connection->getMessage($parentid);
			while ($message instanceof Acknowledge) {
				$parentid = $message->getReference();
				$message = $connection->getMessage($parentid);
			}
		} catch (NotFoundMessageException $e) {}

		if ($this->isAcknowledge()) {
			preg_match('~^[+-][0-9]{1,4}~', $this->body->getBodyPart("text/plain"), $match);
			return new Acknowledge($messageid, $parentid, $date, $author, intval($match[0]) );
		}

		// Nachrichteninhalt
		$signature = null;
		$textbody = $this->body->getBodyPart("text/plain");
		if (strpos($textbody, "-- \n") === 0) {
			$textbody = "";
			$signature = substr($textbody, 4);
		} else if (strpos($textbody, "\n-- \n") !== false) {
			list($textbody, $signature) = explode("\n-- \n", $textbody, 2);
		}
		// Workaround fuer Mailman und andere Clients
		if ($signature == null && preg_match('~^[-_]{2,}\r?$~m', $textbody) > 0) {
			list($textbody, $signature) = preg_split('~^[-_]{2,}\r?$~m', $textbody, 2);
		}

		$htmlbody = $this->body->getBodyPart("text/html");

		$message = new Message($messageid, $date, $author, $subject, $parentid, $textbody, $signature, $htmlbody);

		/* Strukturanalyse des Bodys */
		foreach ($this->body->getAttachmentParts() AS $attachment) {
			$message->addAttachment($attachment->getObject());
		}

		return $message;
	}
}

?>
