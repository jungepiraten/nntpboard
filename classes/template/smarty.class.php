<?php

require_once(dirname(__FILE__) . "/../template.class.php");

require_once("/usr/share/php/Smarty/Smarty.class.php");

/**
 * NOTE: _all_ view* functions quit execution!
 */

class NNTPBoardSmarty extends Smarty implements Template {
	private $config;
	
	public function __construct($config, $auth) {
		parent::__construct();
		$this->config = $config;
		$this->register_function("getlink", array($this, getLink));
		$this->assign("CHARSET", $config->getCharset());
		$this->assign("DATADIR", $config->getDataDir());
		$this->assign("auth", $auth);
	}
	
	function getLink($params) {
		return $this->config->getDataDir()->getWebPath($params["file"]);
	}



	public function viewexception($exception) {
		$this->assign("message", $exception->getMessage());
		$this->display("exception.html.tpl");
		exit;
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
		exit;
	}
	
	public function viewloginform() {
		$this->display("loginform.html.tpl");
		exit;
	}

	public function viewloginfailed() {
		$this->assign("loginfailed", true);
		$this->display("loginform.html.tpl");
		exit;
	}

	public function viewloginsuccess($auth) {
		// Da sich nach einem erfolgreichen Login das Authobjekt geaendert hat, updaten wir hier mal schnell
		$this->assign("auth", $auth);
		
		header("Location: userpanel.php");
		exit;
	}

	public function viewlogoutsuccess() {
		// Da sich nach einem erfolgreichen Logout das Authobjekt geaendert hat, updaten wir hier mal schnell
		$this->assign("auth", null);
		
		header("Location: userpanel.php");
		exit;
	}
	
	public function viewuserpanel() {
		$this->display("userpanel.html.tpl");
		exit;
	}
}

?>
