<?php

class Acknowledge {
	private $messageid;
	private $reference;
	private $date;
	private $author;
	private $wertung;
	
	public function __construct($messageid, $reference, $date, $author, $wertung = +1) {
		$this->messageid = $messageid;
		$this->reference = $reference;
		$this->date = $date;
		$this->author = $author;
		$this->wertung = $wertung;
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

	public function getWertung() {
		return $this->wertung;
	}
}

?>
