<?php

class Cancel {
	private $messageid;
	private $reference;
	private $date;
	private $author;
	
	public function __construct($messageid, $reference, $date, $author) {
		$this->messageid = $messageid;
		$this->reference = $reference;
		$this->date = $date;
		$this->author = $author;
	}
	
	public function getMessageID() {
		return $this->messageid;
	}
	
	public function getReference() {
		return $this->reference;
	}

	public function getDate() {
		return $this->date;
	}

	public function getAuthor() {
		return $this->author;
	}
}

?>
