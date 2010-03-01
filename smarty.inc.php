<?php

require_once("/usr/share/php/Smarty/Smarty.class.php");

abstract class NNTPBoardSmarty extends Smarty {
	private $config;
	
	public function __construct($config) {
		$this->config = $config;
		$this->assign("datadir", $config->getDataDir());
	}
}

class ViewBoardSmarty extends NNTPBoardSmarty {
	public function display() {
		parent::display("viewboard.html.tpl");
	}
}

class ViewThreadSmarty extends NNTPBoardSmarty {
	public function display() {
		parent::display("viewthread.html.tpl");
	}
}

?>
