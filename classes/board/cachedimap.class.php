<?php

require_once(dirname(__FILE__) . "/imap.class.php");

abstract class CachedIMAPBoard extends IMAPBoard {
	public function __construct($boardid, $parentid, $name, $desc, $indexer, $readAuthManager, $writeAuthManager, $isModerated, $host, $loginusername, $loginpassword, $folder, $writer) {
		parent::__construct($boardid, $parentid, $name, $desc, $indexer, $readAuthManager, $writeAuthManager, $isModerated, $host, $loginusername, $loginpassword, $folder, $writer);
	}
}

?>
