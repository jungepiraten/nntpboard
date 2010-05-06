<?php

require_once(dirname(__FILE__)."/attachment.class.php");
require_once(dirname(__FILE__)."/exceptions/message.exception.php");

class Message {
	private $articlenum;
	private $messageid;
	private $parentid = null;
	private $charset = "UTF-8";
	private $subject;
	private $date;
	private $author;
	private $textbody;
	private $htmlbody;
	
	private $attachments = array();
	private $childs = array();
	
	private $group;
	
	public function __construct($group, $articlenum, $messageid, $date, $author, $subject, $charset, $parentid, $textbody, $htmlbody = null) {
		$this->group = $group;
		$this->articlenum = $articlenum;
		$this->messageid = $messageid;
		$this->date = $date;
		$this->author = $author;
		$this->subject = $subject;
		$this->charset = $charset;
		$this->parentid = $parentid;
		$this->textbody = $textbody;
		$this->htmlbody = $htmlbody;
	}
	
	public function getArticleNum() {
		return $this->articlenum;
	}
	
	public function getMessageID() {
		return $this->messageid;
	}

	public function hasParent() {
		return $this->parentid !== null;
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

	public function getText($charset = null) {
		if ($charset !== null) {
			return iconv($this->getCharset(), $charset, $this->getText());
		}
		return $this->textbody;
	}

	public function getHTML($charset = null) {
		if ($charset !== null) {
			return iconv($this->getCharset(), $charset, $this->getHTML());
		}
		if (isset($this->htmlbody) && trim($this->htmlbody) != "") {
			$text = $this->htmlbody;
			// TODO filtern
			$text = strip_tags($text, "<b><i><u><a>");
		} else {
			$text = $this->getText();
			$text = nl2br($text);
			// TODO formatierung
		}
		return $text;
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
	
	/*TODO umlagern ! public function saveAttachments($datadir) {
		// Speichere alle Attachments ab
		foreach ($this->parts AS $partid => &$part) {
			if ($part->isAttachment()) {
				$filename = $datadir->getAttachmentPath($this->group, $part);
				if (!file_exists($filename)) {
					$part->saveAsFile($filename);
				}
			}
		}
	}*/
	
	public function isMime() {
		return ($this->mime !== null);
	}

	public function getMimeType() {
		return $this->mime;
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
	
	public function getGroup() {
		return $this->group;
	}
}

?>
