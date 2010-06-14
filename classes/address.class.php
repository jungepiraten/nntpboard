<?php

class Address {
	private $name;
	private $addr;
	private $comment;
	private $charset;

	public function __construct($name, $addr, $comment = null, $charset = "UTF-8") {
		$this->name = $name;
		$this->addr = $addr;
		$this->comment = $comment;
		$this->charset = $charset;
	}

	public function hasName() {
		return !empty($this->name);
	}

	public function getName($charset = null) {
		if ($charset !== null) {
			return iconv($this->getCharset(), $charset, $this->getName());
		}
		return $this->name;
	}

	public function getAddress($charset = null) {
		if ($charset !== null) {
			return iconv($this->getCharset(), $charset, $this->getAddress());
		}
		return $this->addr;
	}

	public function hasComment() {
		return !empty($this->comment);
	}

	public function getComment($charset = null) {
		if ($charset !== null) {
			return iconv($this->getCharset(), $charset, $this->getComment());
		}
		return $this->comment;
	}

	public function getCharset() {
		return $this->charset;
	}

	public function __toString() {
		return $this->hasName() ? $this->getName() : ($this->hasComment() ? $this->getComment() : $this->getAddress());
	}
}

?>
