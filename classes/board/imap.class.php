<?php

require_once(dirname(__FILE__) . "/../board.class.php");
require_once(dirname(__FILE__) . "/../connection/messagestream/imap.class.php");

class IMAPBoard extends Board {
	private $host;
	private $loginusername;
	private $loginpassword;
	private $folder;

	public function __construct($boardid, $parentid, $name, $desc, $indexer, $readAuthManager, $writeAuthManager, $isModerated, $host, $loginusername, $loginpassword, $folder) {
		parent::__construct($boardid, $parentid, $name, $desc, $indexer, $readAuthManager, $writeAuthManager, $isModerated);
		$this->host = $host;
		$this->loginusername = $loginusername;
		$this->loginpassword = $loginpassword;
		$this->folder = $folder;
	}

	public function hasThreads() {
		return true;
	}

	public function getConnection() {
		return new IMAPConnection($this->host, $this->loginusername, $this->loginpassword, $this->folder, $this->getBoardIndexer());
	}
}

?>
