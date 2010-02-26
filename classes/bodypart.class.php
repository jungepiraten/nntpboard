<?php

class BodyPart {
	// Defaultwerte
	private static $defaultparameters = array(
			"CHARSET" => "UTF-8"
		);

	private $struct;
	private $text;
	private $parameters = array();

	public function __construct($struct, $text) {
		$this->struct = $struct;
		$this->text = $text;
		
		foreach ($struct->parameters AS $param) {
			$this->setParameter($param->attribute, $param->value);
		}
	}
	
	public function getText($charset = null) {
		$text = $this->text;
		// See http://www.php.net/imap_fetchstructure
		switch ($this->struct->encoding) {
		case 0:	$text = $text;			break;
		case 1:	$text = $text;			break;
		case 2:	$text = imap_binary($text);	break;
		case 3:	$text = imap_base64($text);	break;
		case 4:	$text = imap_qprint($text);	break;
		case 5:	$text = $text;			break;
		}
		if ($charset !== null) {
			$text = iconv($this->getCharset(), $charset, $text);
		}
		
		return $text;
	}

	public function getMimeType() {
		$mime = null;
		// See http://www.php.net/manual/en/function.imap-fetchstructure.php
		switch ($this->struct->type) {
		case 0:	$mime = "text";		break;
		case 1:	$mime = "multitype";	break;
		case 2:	$mime = "message";	break;
		case 3:	$mime = "application";	break;
		case 4:	$mime = "audio";	break;
		case 5:	$mime = "image";	break;
		case 6:	$mime = "video";	break;
		case 7:	$mime = "other";	break;
		}
		return ($mime === null ? null : $mime . ($this->struct->ifsubtype ? "/".strtolower($this->struct->subtype) : ""));
	}

	public function isInline() {
		return true;
	}

	public function isText() {
		return ($this->struct->type == 0);
	}
	
	public function isImage() {
		return ($this->struct->type == 5);
	}
	
	public function isVideo() {
		return ($this->struct->type == 6);
	}
	
	public function getSize() {
		return $this->struct->bytes;
	}
	
	public function getFilename() {
		return $this->getParameter("name");
	}
	
	public function getCharset() {
		return $this->getParameter("charset");
	}
	
	public function getDisposition() {
		if ($this->struct->ifdisposition) {
			return strtolower($this->struct->disposition);
		}
		return null;
	}

	public function setParameter($name, $value) {
		$name = strtolower($name);
		$this->parameters[$name] = $value;
	}

	public function getParameter($name) {
		$name = strtolower($name);
		return isset($this->parameters[$name]) ? $this->parameters[$name] :
			(isset(self::$defaultparameters[$name]) ? self::$defaultparameters[$name] : null);
	}
}

?>
