<?php

require_once("/usr/share/php/Smarty/Smarty.class.php");

abstract class NNTPBoardSmarty extends Smarty {
	private $config;
	
	public function __construct($config, $auth) {
		$this->config = $config;
		$this->register_function("redirect", smarty_function_redirect);
		$this->register_function("getlink", array($this, getLink));
		$this->assign("CHARSET", $config->getCharset());
		$this->assign("DATADIR", $config->getDataDir());
		$this->assign("auth", $auth);
	}
	
	function getLink($params) {
		return $this->config->getDataDir()->getWebPath($params["file"]);
	}
	
	public function viewboard($board, $group, $threads = null, $mayPost = false) {
		$this->assign("board", $board);
		$this->assign("threads", $threads);
		$this->assign("mayPost", $mayPost);
		$this->display("viewboard.html.tpl");
		exit;
	}
	
	public function viewthread($board, $thread, $messages, $mayPost = false) {
		$this->assign("board", $board);
		$this->assign("thread", $thread);
		$this->assign("messages", $messages);
		$this->assign("mayPost", $mayPost);
		$this->display("viewthread.html.tpl");
		exit;
	}
	
	public function viewmessage($board, $thread, $message, $mayPost = false) {
		header("Location: viewthread.php?boardid={$board->getBoardID()}&threadid={$message->getThreadID()}#article{$message->getArticleNum()}");
		exit;
	}

	public function viewpostform($board, $reference = null) {
		$subject = "";
		if ($reference !== null) {
			$subject = $reference->getSubject();
			$this->assign("reference", $reference->getMessageID());
			$this->assign("subject", (!in_array(substr($subject,0,3), array("Re:","Aw:")) ? "Re: ".$subject : $subject));
		}
		
		$this->assign("board", $board);
		$this->display("postform.html.tpl");
		exit;
	}

	public function viewpostsuccess($board, $message) {
		$this->viewmessage($board, null, $message, true);
	}
	
	public function viewloginform($loginfailed = false) {
		$this->assign("loginfailed", $loginfailed);
		$this->display("loginform.html.tpl");
		exit;
	}

	public function viewloginfailed() {
		$this->viewloginform(true);
	}

	public function viewloginsuccess($auth) {
		$this->assign("auth", $auth);
		echo "Login erfolgreich! Back dir nen eis!";
	}

	public function viewlogoutsuccess() {
		$this->assign("auth", null);
		echo "Du wurdest abgemeldet! Freu dich!";
	}
	
	public function viewuserpanel() {
		$this->display("userpanel.html.tpl");
	}
}

class ViewBoardSmarty extends NNTPBoardSmarty {}
class ViewThreadSmarty extends NNTPBoardSmarty {}
class PostSmarty extends NNTPBoardSmarty {}
class UserPanelSmarty extends NNTPBoardSmarty {}

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
