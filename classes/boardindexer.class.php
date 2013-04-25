<?php

class BoardIndexer {
	private $boardid;
	private $indexer;

	public function __construct($boardid, $indexer) {
		$this->boardid = $boardid;
		$this->indexer = $indexer;
	}

	public function addMessage(Message $message) {
		if ($this->indexer != null) {
			$this->indexer->addMessage($this->boardid, $message);
		}
	}

	public function removeMessage(Message $message) {
		if ($this->indexer != null) {
			$this->indexer->removeMessage($this->boardid, $message);
		}
	}
}
