<?php

require_once(dirname(__FILE__)."/bodypart.class.php");

class Message {
	private $messageid;
	private $references;
	private $subject;
	private $date;
	private $sender;
	private $parts = array();
	private $childs = array();
	
	private $group;
	
	public function __construct($group, $header) {
		$this->messageid = $header->message_id;
		$this->references = preg_replace("#\s+#", " ", $header->references);
		$this->references = empty($this->references) ? array() : explode(" ", $this->references);
		// TODO: manche subjects sind encodiert: =?UTF-8?B?QW5rw7xuZGlndW5nIFVtenVnIEphYmJlcnNlcnZlciAyMy4wMi4yMDEw?= oder auch =?utf-8?q?Petition_=22Datenschutz_-_Einstufung_von_Systemoperato?= =?utf-8?q?ren_und_Administratoren_als_Berufsgeheimnistr=C3=A4ger_per_Gese?= =?utf-8?b?dHoi?=
		$this->subject = $header->subject;
		$this->date = $header->udate;
		list($this->sender, $domain) = explode('@', $header->senderaddress);
		// $domain sollte nun immer gleich sein ;)
		$this->group = $group;
	}
	
	public function getMessageID() {
		return $this->messageid;
	}
	
	public function getThreadID() {
		return empty($this->references) ? $this->getMessageID() : array_shift(array_slice($this->references,0,1));
	}

	public function hasParentID() {
		return count($this->references) > 0;
	}

	public function getParentID() {
		if (! $this->hasParentID()) {
			return null;
		}
		return array_shift(array_slice($this->references, -1));
	}

	public function getSubject() {
		return $this->subject;
	}

	public function getDate() {
		return $this->date;
	}

	public function setSender($sender) {
		$this->sender = $sender;
	}

	public function getSender() {
		return $this->sender;
	}
	
	public function addBodyPart($i, $struct, $body) {
		$this->parts[$i] = new BodyPart($this, $i, $struct, $body);
	}
	
	public function getBodyParts() {
		return $this->parts;
	}
	
	public function setBodyParts($parts) {
		$this->parts = $parts;
	}
	
	public function getBodyPart($i) {
		return $this->parts[$i];
	}
	
	public function addChild($msg) {
		$this->childs[] = $msg->getMessageID();
	}
	
	public function setGroup($group) {
		$this->group = $group;
	}
	
	public function getGroup() {
		return $this->group;
	}
}

?>
