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
	private $location = null;

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
			return iconv($this->getCharset(), $charset, $this->getText());
		}		if ($this->text === null && $this->location !== null) {
			return file_get_contents($this->location);
		}
		return $this->text;
	}
	
	public function getHTML($charset = null, $allowhtml = false) {
		$text = $this->getText($charset);
		if (in_array(strtolower($this->getMimeType()), array("text/html", "application/xhtml+xml"))) {
			// Erlaube kein HTML! (TODO: 2. Parameter benutzen ;) )
			if (is_string($allowhtml)) {
				$text = strip_tags($text, $allowhtml);
			} elseif ($allowhtml !== true) {
				$text = strip_tags($text);
			}
		} else {
			$text = htmlentities($text, ENT_QUOTES, $charset);
		}

		// TODO Links ersetzen und weitere Formatierung einbauen

		return $text;
	}
	
	public function getLength() {
		return strlen($this->getText());
	}

	/* Content-Disposition */
	public function getDisposition() {
		return $this->disposition;
	}

	public function isInline() {
		return (strtolower($this->disposition) == 'inline');
	}
	
	public function isAttachment() {
		return (strtolower($this->disposition) == 'attachment' || $this->getFilename() !== null);
	}
	
	public function hasFilename() {
		return ($this->filename !== null);
	}
	
	public function getFilename() {
		return $this->filename;
	}

	/* Content-Type */
	public function getMimeType() {
		return ($this->mimetype === null ? null : $this->mimetype . ($this->mimesubtype !== null ? "/".$this->mimesubtype : ""));
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
	
	public function getCharset() {
		return $this->charset;
	}

	public function saveAsFile($filename) {
		if ($this->location === null) {
			$this->location = $filename;
		}
		file_put_contents($filename, $this->getText());
	}
}

?>
