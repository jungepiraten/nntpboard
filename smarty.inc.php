<?php

require_once("/usr/share/php/Smarty/Smarty.class.php");

abstract class NNTPBoardSmarty extends Smarty {
	private $config;
	
	public function __construct($config) {
		$this->config = $config;
		$this->register_function("getlink", array($this, getLink));
		$this->assign("CHARSET", "UTF-8");
		$this->assign("DATADIR", $config->getDataDir());
	}
	
	function getLink($params) {
		return $this->get_template_vars("DATADIR")->getWebPath($params["file"]);
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
