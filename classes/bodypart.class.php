<?php

class BodyPart {
	private $messageid;
	private $partid;
	private $text;
	private $charset = "UTF-8";
	private $filename = null;
	private $disposition = null;
	private $mimetype = null;
	private $mimesubtype = null;

	public function __construct($message, $partid, $disposition, $mimetype, $text, $charset = "UTF-8", $filename = null) {
		$this->messageid = $message->getMessageID();
		$this->partid = $partid;
		if (!empty($disposition)) {
			$this->disposition = strtolower($disposition);
		}
		if (!empty($mimetype)) {
			list ($this->mimetype, $this->mimesubtype) = explode("/", $mimetype, 2);
			if (empty($this->mimetype)) {
				$this->mimetype = null;
			}
			if (empty($this->mimesubtype)) {
				$this->mimesubtype = null;
			}
		}
		$this->text = $text;
		$this->charset = $charset;
		$this->filename = $filename;
	}
	
	public function getMessageID() {
		return $this->messageid;
	}
	
	public function getPartID() {
		return $this->partid;
	}
	
	public function getText($charset = null) {
		if ($charset !== null) {
			$text = iconv($this->getCharset(), $charset, $this->getText());
		}
		// TODO aus attachment laden
		return ($this->text === null ? null : $this->text);
	}
	
	public function getHTML($charset = null) {
		$text = $this->getText($charset);
		// TODO $text bearbeiten :/
		return $text;
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
		return strlen($this->text);
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
