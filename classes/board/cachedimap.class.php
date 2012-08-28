<?php

require_once(dirname(__FILE__) . "/imap.class.php");

abstract class CachedIMAPBoard extends IMAPBoard {
	public function __construct($boardid, $parentid, $name, $desc, $anonMayPost, $authMayPost, $isModerated, $host, $loginusername, $loginpassword, $folder) {
		parent::__construct($boardid, $parentid, $name, $desc, $anonMayPost, $authMayPost, $isModerated, $host, $loginusername, $loginpassword, $folder);
	}
}

?>