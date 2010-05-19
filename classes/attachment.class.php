<?php

class Attachment {
	private $content;
	private $filename = null;
	private $disposition = null;
	private $mimetype = null;
	private $mimesubtype = null;

	public function __construct($disposition, $mimetype, $content, $filename = null) {
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
		$this->content = $content;
		$this->filename = $filename;
	}
	
	/**
	 * getText()
	 * @return	String
	 *	Inhalt des Anhangs
	 **/
	public function getContent() {
		if ($this->content !== null) {
			return $this->content;
		}
	}
	
	public function getLength() {
		return strlen($this->getContent());
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
}

?>
