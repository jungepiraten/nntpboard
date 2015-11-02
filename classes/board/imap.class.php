<?php

require_once(dirname(__FILE__) . "/../board.class.php");
require_once(dirname(__FILE__) . "/../connection/messagestream/imap.class.php");

class IMAPBoard extends Board {
	private $host;
	private $loginusername;
	private $loginpassword;
	private $folder;
	private $writer;

	public function __construct($boardid, $parentid, $name, $desc, $indexer, $readAuthManager, $writeAuthManager, $host, $loginusername, $loginpassword, $folder, $writer) {
		parent::__construct($boardid, $parentid, $name, $desc, $indexer, $readAuthManager, $writeAuthManager);
		$this->host = $host;
		$this->loginusername = $loginusername;
		$this->loginpassword = $loginpassword;
		$this->folder = $folder;
		$this->writer = $writer;
	}

	public function hasThreads() {
		return true;
	}

	public function getConnection() {
		return new IMAPConnection($this->host, $this->loginusername, $this->loginpassword, $this->folder, $this->getBoardIndexer(), $this->writer);
	}
}

?>
