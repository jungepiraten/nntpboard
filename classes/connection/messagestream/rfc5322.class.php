<?php

require_once(dirname(__FILE__)."/../messagestream.class.php");
require_once(dirname(__FILE__)."/rfc5322/header.class.php");
require_once(dirname(__FILE__)."/rfc5322/message.class.php");

abstract class AbstractRFC5322Connection extends AbstractMessageStreamConnection {
	public function getMessage($msgid) {
		$rfcmessage = $this->getRFC5322Message($msgid);
		return $rfcmessage->getObject($msgid, $this);
	}

	abstract protected function getRFC5322Message($msgid);

	public function postMessage($message) {
		return $this->post( RFC5322Message::parseObject($this, $message) );
	}

	public function postAcknowledge($ack, $message) {
		return $this->post( RFC5322Message::parseAcknowledgeObject($this, $ack, $message) );
	}

	public function postCancel($cancel, $message) {
		return $this->post( RFC5322Message::parseCancelObject($this, $cancel, $message) );
	}

	abstract protected function post($rfc5322msg);
}

?>
