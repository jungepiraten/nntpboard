<?php

require_once("/usr/share/php/Smarty/Smarty.class.php");

abstract class NNTPBoardSmarty extends Smarty {}

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
