<?php

require_once(dirname(__FILE__)."/bodypart.class.php");

class Message {
	private $messageid;
	private $references;
	private $charset = "UTF-8";
	private $subject;
	private $date;
	private $sender;
	private $parts = array();
	private $childs = array();
	
	private $group;
	
	public function __construct($group, $messageid, $date, $sender, $subject, $charset, $references) {
		$this->group = $group;
		$this->messageid = $messageid;
		$this->date = $date;
		$this->sender = $sender;
		$this->subject = $subject;
		$this->charset = $charset;
		$this->references = $references;
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
	
	public function getCharset() {
		return $this->charset;
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
	
	public function saveAttachments($datadir) {
		/* Speichere alle Attachments ab */
		foreach ($this->parts AS $partid => &$part) {
			if ($part->isAttachment()) {
				$filename = $datadir->getAttachmentPath($this->group, $part);
				if (!file_exists($filename)) {
					file_put_contents($filename, $part->getText());
				}
				// Sonst speichern wir alles doppelt
				$part->setText(null);
			}
		}
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
