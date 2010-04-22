<?php

require_once(dirname(__FILE__)."/bodypart.class.php");

class Message {
	private $articlenum;
	private $messageid;
	private $threadid;
	private $parentid;
	private $charset = "UTF-8";
	private $subject;
	private $date;
	private $sender;
	private $parts = array();
	private $childs = array();
	
	private $group;
	
	public function __construct($group, $articlenum, $messageid, $date, $sender, $subject, $charset, $threadid, $parentid) {
		$this->group = $group;
		$this->articlenum = $articlenum;
		$this->messageid = $messageid;
		$this->date = $date;
		$this->sender = $sender;
		$this->subject = $subject;
		$this->charset = $charset;
		$this->threadid = $threadid;
		$this->parentid = $parentid;
	}
	
	public function getArticleNum() {
		return $this->articlenum;
	}
	
	public function getMessageID() {
		return $this->messageid;
	}
	
	public function getThreadID() {
		return $this->threadid !== null ? $this->threadid : $this->getMessageID();
	}

	public function hasParentID() {
		return $this->parentid !== null;
	}

	public function getParentID() {
		if (! $this->hasParentID()) {
			return null;
		}
		return $this->parentid;
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
	
	public function addBodyPart(BodyPart $bodypart) {
		if ($bodypart->getMessageID() != $this->getMessageID()) {
			throw new Exception("MessageID not matching while trying to add a bodypart");
		}
		$this->parts[$bodypart->getPartID()] = $bodypart;
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
	
	public function getGroup() {
		return $this->group;
	}
}

?>
