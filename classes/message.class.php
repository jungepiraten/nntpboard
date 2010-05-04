<?php

require_once(dirname(__FILE__)."/bodypart.class.php");
require_once(dirname(__FILE__)."/exceptions/message.exception.php");

if (!function_exists("quoted_printable_encode")) {
	// aus http://de.php.net/quoted_printable_decode
	function quoted_printable_encode($string) {
		$string = str_replace(array('%20', '%0D%0A', '%'), array(' ', "\r\n", '='), rawurlencode($string));
		$string = preg_replace('/[^\r\n]{73}[^=\r\n]{2}/', "$0=\r\n", $string);
		return $string;
	}
}

class Message {
	private $articlenum;
	private $messageid;
	private $threadid;
	private $parentid = null;
	private $parentartnum = null;
	private $charset = "UTF-8";
	private $subject;
	private $date;
	private $author;
	private $mime = null;
	private $parts = array();
	private $childs = array();
	
	private $group;
	
	public function __construct($group, $articlenum, $messageid, $date, $author, $subject, $charset, $threadid, $parentid, $parentartnum) {
		$this->group = $group;
		$this->articlenum = $articlenum;
		$this->messageid = $messageid;
		$this->date = $date;
		$this->author = $author;
		$this->subject = $subject;
		$this->charset = $charset;
		$this->threadid = $threadid;
		$this->parentid = $parentid;
		$this->parentartnum = $parentartnum;
		$this->mime = $mime;
	}
	
	public function getArticleNum() {
		return $this->articlenum;
	}
	
	public function getMessageID() {
		return $this->messageid;
	}
	
	public function getThreadID() {
		return $this->threadid !== null ? $this->threadid : $this->getArticleNum();
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

	public function getParentArtNum() {
		if (! $this->hasParent()) {
			return null;
		}
		return $this->parentartnum;
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

	public function getAuthor($charset = null) {
		if ($charset !== null) {
			return iconv($this->getCharset(), $charset, $this->getAuthor());
		}
		return $this->author;
	}
	
	public function getCharset() {
		return $this->charset;
	}
	
	public function addBodyPart(BodyPart $bodypart) {
		if ($bodypart->getMessageID() != $this->getMessageID()) {
			throw new MessageIDNotMatchingMessageException($this);
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
					$part->saveAsFile($filename);
				}
			}
		}
	}
	
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
