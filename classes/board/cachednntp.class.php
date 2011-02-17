<?php

require_once(dirname(__FILE__) . "/nntp.class.php");

abstract class CachedNNTPBoard extends NNTPBoard {
	public function __construct($boardid, $parentid, $name, $desc, $anonMayPost, $authMayPost, $isModerated, $host, $group) {
		parent::__construct($boardid, $parentid, $name, $desc, $anonMayPost, $authMayPost, $isModerated, $host, $group);
	}
}

?>
