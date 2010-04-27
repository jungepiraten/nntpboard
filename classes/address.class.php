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

	public function getIMFString() {
		return (!empty($this->name) ? "{$this->name} <{$this->addr}>" : $this->addr) . (!empty($this->comment) ? " ({$this->comment})" : "");
	}

	public function __toString() {
		return !empty($this->name) ? $this->name : (!empty($this->comment) ? $this->comment : $this->addr);
	}
}

?>
