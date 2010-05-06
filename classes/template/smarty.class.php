<?php

require_once(dirname(__FILE__) . "/../template.class.php");

require_once("/usr/share/php/Smarty/Smarty.class.php");

/**
 * NOTE: _all_ view* functions quit execution!
 */

class NNTPBoardSmarty extends AbstractTemplate implements Template {
	private $smarty;
	
	public function __construct($charset, $auth, $threadsperpage = null, $messagesperpage = null) {
		parent::__construct($charset, $auth, $threadsperpage, $messagesperpage);
		$this->config = $config;
		$this->charset = $charset;
		$this->auth = $auth;

		$this->smarty = new Smarty;
		$this->smarty->assign("CHARSET", $this->getCharset());
		$this->smarty->assign("ISANONYMOUS", $this->getAuth()->isAnonymous());
		$this->smarty->assign("ADDRESS", $this->getAuth()->getAddress($this->getCharset()));
	}


	private function parseBoard($board, $parseParent = true) {
		$row = array();
		$row["boardid"]		= $board->getBoardID();
		if ($board->hasParent() && $parseParent == true) {
			$row["parent"]	= $this->parseBoard($board->getParent());
		}
		$row["name"]		= $board->getName();
		$row["desc"]		= $board->getDesc();
		// TODO letzter Post einbauen
		if ($board->hasSubBoards()) {
			$row["childs"]	= array();
			foreach ($board->getSubBoards() AS $child) {
				// Kleiner Hack, um eine Endlosschleife zu vermeiden
				$child = $this->parseBoard($child, false);
				$child["parent"] = &$row;
				$row["childs"][] = $child;
			}
		}
		return $row;
	}

	private function parseThread($thread) {
		$row = array();
		$row["threadid"]		= $thread->getThreadID();
		$row["subject"]			= $thread->getSubject($this->getCharset());
		$row["posts"]			= $thread->getPosts();
		$row["date"]			= $thread->getDate();
		$row["author"]			= $this->parseAddress($thread->getAuthor());
		$row["lastpostmessageid"]	= $thread->getLastPostMessageID();
		$row["lastpostdate"]		= $thread->getLastPostDate();
		$row["lastpostauthor"]		= $thread->getLastPostAuthor($this->getCharset());
		$row["unread"]			= $this->getAuth()->isUnreadThread($thread);
		return $row;
	}

	private function parseMessage($message) {
		$row = array();
		$row["articlenum"]	= $message->getArticleNum();
		$row["messageid"]	= $message->getMessageID();
		$row["subject"]		= $message->getSubject($this->getCharset());
		$row["author"]		= $this->parseAddress($message->getAuthor());
		$row["date"]		= $message->getDate();
		$row["body"]		= $message->getHTML($this->getCharset());
		$row["attachments"]	= array();
		foreach ($message->getAttachments() AS $partid => $attachment) {
			$row["attachments"][$partid] = $this->parseAttachment($attachment);
		}
		return $row;
	}

	private function parseAttachment($attachment) {
		$row = array();
		$row["isinline"]	= $attachment->isInline();
		$row["istext"]		= $attachment->isText();
		$row["isimage"]		= $attachment->isImage();
		$row["filename"]	= $attachment->getFilename();
		return $row;
	}

	private function parseAddress($address) {
		if ($address === null) {
			return null;
		}
		return $address->__toString($this->getCharset());
	}



	public function viewexception($exception) {
		$this->smarty->assign("message", $exception->getMessage());
		$this->smarty->display("exception.html.tpl");
		exit;
	}
	
	public function viewboard($board, $group, $threadobjs = null, $mayPost = false) {
		$threads = array();
		if (is_array($threadobjs)) {
			foreach ($threadobjs AS $thread) {
				$threads[] = $this->parseThread($thread);
			}
		}
		
		$page = 0;
		$pages = ceil(count($threads) / $this->getThreadsPerPage());
		if (isset($_REQUEST["page"])) {
			$page = intval($_REQUEST["page"]);
		}
		// Vorsichtshalber erlauben wir nur Seiten, auf dennen auch Nachrichten stehen
		if ($page < 0 || $page > $pages) {
			$page = 0;
		}
		
		$this->smarty->assign("page", $page);
		$this->smarty->assign("pages", $pages);

		$this->smarty->assign("board", $this->parseBoard($board));
		$this->smarty->assign("threads", array_slice($threads, $page * $this->getThreadsPerPage(), $this->getThreadsPerPage()));
		$this->smarty->assign("mayPost", $mayPost);
		$this->smarty->display("viewboard.html.tpl");
		exit;
	}
	
	public function viewthread($board, $thread, $messagesobjs, $mayPost = false) {
		$messages = array();
		if (is_array($messagesobjs)) {
			foreach ($messagesobjs AS $message) {
				$messages[] = $this->parseMessage($message);
			}
		}
		
		$page = 0;
		$pages = ceil(count($messages) / $this->getMessagesPerPage());
		if (isset($_REQUEST["page"])) {
			$page = intval($_REQUEST["page"]);
		}
		// Vorsichtshalber erlauben wir nur Seiten, auf dennen auch Nachrichten stehen
		if ($page < 0 || $page > $pages) {
			$page = 0;
		}
		
		$this->smarty->assign("page", $page);
		$this->smarty->assign("pages", $pages);
		$this->smarty->assign("board", $this->parseBoard($board));
		$this->smarty->assign("thread", $this->parseThread($thread));
		$this->smarty->assign("messages", array_slice($messages, $page * $this->getMessagesPerPage(), $this->getMessagesPerPage()));
		$this->smarty->assign("mayPost", $mayPost);
		$this->smarty->display("viewthread.html.tpl");
		exit;
	}
	
	public function viewmessage($board, $thread, $message, $mayPost = false) {
		// TODO auch auf die richtige seite weiterleiten :o
		$page = 0;
		header("Location: viewthread.php?boardid=" . urlencode($board->getBoardID()) .
		                  "&threadid=" . urlencode($thread->getThreadID()) .
		                  "&page=" . intval($page) . "#article" . 
		                  urlencode($message->getArticleNum()));
		exit;
	}

	public function viewpostform($board, $reference = null) {
		$subject = "";
		if ($reference !== null) {
			$subject = $reference->getSubject();
			$this->smarty->assign("reference", $reference->getMessageID());
			$this->smarty->assign("subject", (!in_array(substr($subject,0,3), array("Re:","Aw:")) ? "Re: ".$subject : $subject));
		}

		$this->smarty->assign("address", $this->parseAddress($this->getAuth()->getAddress()));
		
		$this->smarty->assign("board", $this->parseBoard($board));
		$this->smarty->display("postform.html.tpl");
		exit;
	}

	public function viewpostsuccess($board, $thread, $message) {
		$this->viewmessage($board, $thread, $message, true);
		exit;
	}

	public function viewpostmoderated($board, $thread, $message) {
		// TODO bestaetigung anzeigen
		exit;
	}
	
	public function viewloginform() {
		$this->smarty->display("loginform.html.tpl");
		exit;
	}

	public function viewloginfailed() {
		$this->smarty->assign("loginfailed", true);
		$this->smarty->display("loginform.html.tpl");
		exit;
	}

	public function viewloginsuccess($auth) {
		// Da sich nach einem erfolgreichen Login das Authobjekt geaendert hat, updaten wir hier mal schnell
		$this->smarty->assign("auth", $auth);
		
		header("Location: userpanel.php");
		exit;
	}

	public function viewlogoutsuccess() {
		// Da sich nach einem erfolgreichen Logout das Authobjekt geaendert hat, updaten wir hier mal schnell
		$this->smarty->assign("auth", null);
		
		header("Location: userpanel.php");
		exit;
	}
	
	public function viewuserpanel() {
		$this->smarty->display("userpanel.html.tpl");
		exit;
	}
}

?>
