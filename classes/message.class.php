<?php
require_once(dirname(__FILE__)."/attachment.class.php");
require_once(dirname(__FILE__)."/exceptions/message.exception.php");

class Message {
	private $messageid;
	private $parentid = null;
	private $charset = "UTF-8";
	private $subject;
	private $date;
	private $author;
	private $textbody;
	private $signature;
	private $htmlbody;

	private $attachments = array();
	private $childs = array();
	
	public function __construct($messageid, $date, $author, $subject, $charset, $parentid, $textbody, $signature = null, $htmlbody = null) {
		$this->messageid = $messageid;
		$this->date = $date;
		$this->author = $author;
		$this->subject = $subject;
		$this->charset = $charset;
		$this->parentid = $parentid;
		$this->textbody = $textbody;
		$this->signature = $signature;
		$this->htmlbody = $htmlbody;
	}
	
	public function getMessageID() {
		return $this->messageid;
	}

	public function hasParent() {
		return $this->parentid !== null;
	}

	public function setParent($parent) {
		if ($parent !== null) {
			$this->setParentID($parent->getMessageID());
		}
	}

	public function setParentID($parentid) {
		$this->parentid = $parentid;
	}

	public function getParentID() {
		if (! $this->hasParent()) {
			return null;
		}
		return $this->parentid;
	}

	public function getSubject($charset = null) {
		if ($charset !== null) {
			return iconv($this->getCharset(), $charset, $this->getSubject());
		}
		return $this->subject;
	}

	public function getDate() {
		return $this->date;
	}

	public function getAuthor() {
		return $this->author;
	}

	public function hasTextBody() {
		return isset($this->textbody) && trim($this->textbody) != "";
	}

	public function getTextBody($charset = null) {
		if ($charset !== null) {
			return iconv($this->getCharset(), $charset, $this->getTextBody());
		}
		return $this->textbody;
	}

	public function hasSignature() {
		return isset($this->signature) && trim($this->signature) != "";
	}

	public function getSignature($charset = null) {
		if ($charset !== null) {
			return iconv($this->getCharset(), $charset, $this->getSignature());
		}
		return $this->signature;
	}

	public function hasHTMLBody() {
		return isset($this->htmlbody) && trim($this->htmlbody) != "";
	}

	public function getHTMLBody($charset = null) {
		if ($charset !== null) {
			return iconv($this->getCharset(), $charset, $this->getHTMLBody());
		}
		return $this->htmlbody;
	}
	
	public function getCharset() {
		return $this->charset;
	}
	
	public function addAttachment(Attachment $attachment) {
		$this->attachments[] = $attachment;
	}
	
	public function getAttachments() {
		return $this->attachments;
	}
	
	public function getAttachment($i) {
		return $this->attachments[$i];
	}

	public function getChilds() {
		return array_keys($this->childs);
	}
	
	public function addChild($msg) {
		$this->childs[$msg->getMessageID()] = true;
	}
	
	public function removeChild($msg) {
		unset($this->childs[$msg->getMessageID()]);
	}
}
?>