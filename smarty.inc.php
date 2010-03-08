<?php

require_once("/usr/share/php/Smarty/Smarty.class.php");

abstract class NNTPBoardSmarty extends Smarty {
	private $config;
	
	public function __construct($config) {
		$this->config = $config;
		$this->register_function("redirect", smarty_function_redirect);
		$this->register_function("getlink", array($this, getLink));
		$this->assign("CHARSET", "UTF-8");
		$this->assign("DATADIR", $config->getDataDir());
	}
	
	function getLink($params) {
		return $this->get_template_vars("DATADIR")->getWebPath($params["file"]);
	}
	
	public function viewboard($board, $group, $threads) {
		$this->assign("board", $board);
		$this->assign("threads", $threads);
		$this->display("viewboard.html.tpl");
	}
	
	public function viewthread($board, $thread, $messages) {
		$this->assign("board", $board);
		$this->assign("thread", $thread);
		$this->assign("messages", $messages);
		$this->display("viewthread.html.tpl");
	}
	
	public function viewmessage($board, $thread, $message) {
		$this->assign("board", $board);
		$this->assign("thread", $thread);
		$this->assign("message", $message);
		$this->display("viewmessage.html.tpl");
	}
}

class ViewBoardSmarty extends NNTPBoardSmarty {}
class ViewThreadSmarty extends NNTPBoardSmarty {}

/**
* Smarty {redirect} function plugin
*
* Type: function
* Name: redirect
* Purpose: Do a HTTP redirect
* @link http://www.webmaterials.com
* (Webmaterials website)
* @param array
* @param Smarty
* @return string
*/
function smarty_function_redirect($params, &$smarty) {
	if (!isset($params['url'])) {
		$smarty->triggererror("redirect: missing 'url' parameter");
		return;
	}
	if (empty($params['url'])) {
		$smarty->triggererror("redirect: empty 'url' parameter");
		return;
	}
	header("Location: ".$params['url']);
	$html ='<a href="'.$params['url'].'">'.$params['url'].'</a>';
	return $html;
}

?>
