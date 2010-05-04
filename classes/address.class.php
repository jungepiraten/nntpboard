<?php

class Address {
	private $name;
	private $addr;
	private $comment;

	public function __construct($name, $addr, $comment = null) {
		$this->name = $name;
		$this->addr = $addr;
		$this->comment = $comment;
	}

	public function hasName() {
		return !empty($this->name);
	}

	public function getName() {
		return $this->name;
	}

	public function getAddress() {
		return $this->addr;
	}

	public function hasComment() {
		return !empty($this->comment);
	}

	public function getComment() {
		return $this->comment;
	}

	public function __toString() {
		return !empty($this->name) ? $this->name : (!empty($this->comment) ? $this->comment : $this->addr);
	}
}

?>
