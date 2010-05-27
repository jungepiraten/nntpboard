<?php

require_once(dirname(__FILE__) . "/nntp.class.php");

abstract class CachedNNTPBoard extends NNTPBoard {
	public function __construct($boardid, $parentid, $name, $desc, $host, $group, $anonMayPost, $authMayPost, $isModerated) {
		parent::__construct($boardid, $parentid, $name, $desc, $host, $group, $anonMayPost, $authMayPost, $isModerated);
	}
}

?>
