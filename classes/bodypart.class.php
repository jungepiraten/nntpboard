<?php

class BodyPart {
	private $messageid;
	private $partid;
	private $text;
	private $size;
	private $charset = "UTF-8";
	private $filename = null;
	private $disposition = null;
	private $mimetype = null;
	private $mimesubtype = null;

	public function __construct($message, $partid, $struct, $text) {
		$this->messageid = $message->getMessageID();
		$this->partid = $partid;
		$this->size = $struct->bytes;
		if ($struct->ifdisposition) {
			$this->disposition = strtolower($struct->disposition);
		}
		
		// See http://www.php.net/manual/en/function.imap-fetchstructure.php
		switch ($struct->type) {
		case 0:	$this->mimetype = "text";		break;
		case 1:	$this->mimetype = "multitype";		break;
		case 2:	$this->mimetype = "message";		break;
		case 3:	$this->mimetype = "application";	break;
		case 4:	$this->mimetype = "audio";		break;
		case 5:	$this->mimetype = "image";		break;
		case 6:	$this->mimetype = "video";		break;
		case 7:	$this->mimetype = "other";		break;
		}
		if ($struct->ifsubtype) {
			$this->mimesubtype = strtolower($struct->subtype);
		}
		
		// See http://www.php.net/imap_fetchstructure
		switch ($struct->encoding) {
		case 0:	$text = $text;			break;
		case 1:	$text = $text;			break;
		case 2:	$text = imap_binary($text);	break;
		case 3:	$text = imap_base64($text);	break;
		case 4:	$text = imap_qprint($text);	break;
		case 5:	$text = $text;			break;
		}
		$this->text = $text;
		
		foreach ($struct->parameters AS $param) {
			switch (strtolower($param->attribute)) {
			case 'charset':
				$this->charset = $param->value;
				break;
			case 'name':
				$this->filename = $param->value;
				break;
			}
		}
	}
	
	public function getMessageID() {
		return $this->messageid;
	}
	
	public function getPartID() {
		return $this->partid;
	}
	
	public function getText($charset = null) {
		// TODO aus attachment laden
		$text = ($this->text === null ? null : $this->text);
		if ($charset !== null) {
			$text = iconv($this->getCharset(), $charset, $text);
		}
		
		return $text;
	}
	
	public function setText($text, $charset = null) {
		$this->text = $text;
		if ($charset !== null) {
			$this->charset = $charset;
		}
	}

	public function getMimeType() {
		return ($this->mimetype === null ? null : $mime . ($this->mimesubtype !== null ? "/".$this->mimesubtype : ""));
	}

	public function isInline() {
		// TODO *hust*
		return true;
	}

	public function isText() {
		return (strtolower($this->mimetype) == 'text');
	}
	
	public function isApplication() {
		return (strtolower($this->mimetype) == 'application');
	}
	
	public function isAudio() {
		return (strtolower($this->mimetype) == 'audio');
	}
	
	public function isImage() {
		return (strtolower($this->mimetype) == 'image');
	}
	
	public function isVideo() {
		return (strtolower($this->mimetype) == 'video');
	}
	
	public function isAttachment() {
		return ($this->getFilename() !== null);
	}
	
	public function getSize() {
		return $this->size;
	}
	
	public function getFilename() {
		return $this->filename;
	}
	
	public function getCharset() {
		return $this->charset;
	}
	
	public function getDisposition() {
		return $this->disposition;
	}
}

?>
