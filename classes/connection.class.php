<?php

require_once(dirname(__FILE__)."/message.class.php");

class IMAPConnection {
	private $group;
	private $username;
	private $password;
	private $connection;
	
	public function __construct($group, $username, $password) {
		$this->group = $group;
		$this->username = $username;
		$this->password = $password;
	}
	
	public function open() {
		$this->connection = @imap_open($this->group->getHost()->getGroupString($this->group), $this->username, $this->password);
		if ($this->connection === false) {
			throw new Exception(implode("\n", imap_errors()) . " while connecting to {$this->group->getHost()->getGroupString($this->group)}.");
		}
	}
	
	public function close() {
		imap_close($this->connection);
	}
	
	public function loadMessages() {
		$c = imap_num_msg($this->connection);
		
		$this->group->clear();
		for ($i = 1; $i <= $c; $i++) {
			$this->group->addMessage($this->parseMessage($i));
		}
		$this->group->sort();
	}
	
	public function decodeRFC2045($text, $charset) {
		preg_match_all('#=\?([-a-zA-Z0-9_]+)\?([QqBb])\?(.*)\?=(\s|$)#U', $text, $matches, PREG_SET_ORDER);
		foreach ($matches AS $m) {
			switch (strtolower($m[2])) {
			case 'b':
				$m[3] = imap_base64($m[3]);
				break;
			case 'q':
				$m[3] = str_replace("_", " ", imap_qprint($m[3]));
				break;
			default:
				// prohibited by RFC2045
				continue;
			}
			$text = str_replace($m[0], iconv($m[1], $charset, $m[3]), $text);
		}
		return $text;
	}
	
	public function parseMessage($i) {
		$header = imap_header($this->connection, $i);
		$messageid = $header->message_id;
		$references = preg_replace("#\s+#", " ", $header->references);
		$references = empty($references) ? array() : explode(" ", $references);
		
		// TODO
		$charset = "UTF-8";
		$subject = $this->decodeRFC2045($header->subject, $charset);
		$date = $header->udate;
		list($sender, $domain) = explode('@', $header->senderaddress);
		// $domain sollte nun immer gleich sein ;)
		
		$message = new Message($this->group, $messageid, $date, $sender, $subject, $charset, $references);
		
		// Strukturanalyse
		$struct = imap_fetchstructure($this->connection, $i);
		if (is_array($struct->parts)) {
			foreach ($struct->parts AS $p => $part) {
				$message->addBodyPart($p, $part, imap_fetchbody($this->connection, $i, $p+1));
			}
		} else {
			$message->addBodyPart(0, $struct, imap_body($this->connection, $i));
		}
		
		return $message;
	}
}

?>
