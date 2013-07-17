<?php

require_once(dirname(__FILE__) . "/../writer.class.php");

class SMTPMessageStreamWriter extends AbstractMessageStreamWriter {
	private $host;
	private $bounceaddress;
	private $recipient;

	public function __construct($host, $bounceaddress, $recipient) {
		$this->host = $host;
		$this->bounceaddress = $bounceaddress;
		$this->recipient = $recipient;
	}

	public function post(RFC5322Message $message) {
		$message->getHeader()->setValue("To", $this->recipient);

		$conn = new Net_SMTP($this->host->getHost(), $this->host->getPort());
		$conn->connect();
		$conn->mailFrom($this->bounceaddress);
		$conn->rcptTo($this->recipient);
		$conn->data($message->getPlain());
		$conn->disconnect();
	}
}
