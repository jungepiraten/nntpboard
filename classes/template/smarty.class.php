<?php

require_once(dirname(__FILE__) . "/../template.class.php");

require_once("/usr/share/php/Smarty/Smarty.class.php");

/**
 * NOTE: _all_ view* functions quit execution!
 */

class NNTPBoardSmarty extends Smarty implements Template {
	private $config;
	private $charset;
	private $auth;
	
	public function __construct($charset, $auth) {
		parent::__construct();
		$this->config = $config;
		$this->charset = $charset;
		$this->auth = $auth;

		$this->assign("CHARSET", $this->getCharset());
		$this->assign("ISANONYMOUS", $this->getAuth()->isAnonymous());
		$this->assign("ADDRESS", $this->getAuth()->getAddress($this->getCharset()));
	}

	function getCharset() {
		return $this->charset;
	}

	function getAuth() {
		return $this->auth;
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
		$row["bodyparts"]	= array();
		foreach ($message->getBodyParts() AS $partid => $part) {
			$row["bodyparts"][$partid] = $this->parseBodyPart($part);
		}
		return $row;
	}

	private function parseBodyPart($part) {
		$row = array();
		$row["isinline"]	= $part->isInline();
		$row["istext"]		= $part->isText();
		$row["isimage"]		= $part->isImage();
		$row["text"]		= $part->getHTML($this->getCharset());
		$row["filename"]	= $part->getFilename();
		return $row;
	}

	private function parseAddress($address) {
		if ($address === null) {
			return null;
		}
		return $address->__toString($this->getCharset());
	}



	public function viewexception($exception) {
		$this->assign("message", $exception->getMessage());
		$this->display("exception.html.tpl");
		exit;
	}
	
	public function viewboard($board, $group, $threadobjs = null, $mayPost = false) {
		$threads = array();
		if (is_array($threadobjs)) {
			foreach ($threadobjs AS $thread) {
				$threads[] = $this->parseThread($thread);
			}
		}
		
		// TODO parametisieren
		$threadsperpage = 10;
		$page = 0;
		$pages = ceil(count($threads) / $threadsperpage);
		if (isset($_REQUEST["page"])) {
			$page = intval($_REQUEST["page"]);
		}
		// Vorsichtshalber erlauben wir nur Seiten, auf dennen auch Nachrichten stehen
		if ($page < 0 || $page > $pages) {
			$page = 0;
		}
		
		$this->assign("page", $page);
		$this->assign("pages", $pages);

		$this->assign("board", $this->parseBoard($board));
		$this->assign("threads", array_slice($threads, $page * $threadsperpage, $threadsperpage));
		$this->assign("mayPost", $mayPost);
		$this->display("viewboard.html.tpl");
		exit;
	}
	
	public function viewthread($board, $thread, $messagesobjs, $mayPost = false) {
		$messages = array();
		if (is_array($messagesobjs)) {
			foreach ($messagesobjs AS $message) {
				$messages[] = $this->parseMessage($message);
			}
		}
		
		// TODO parametisieren
		$messagesperpage = 10;
		$page = 0;
		$pages = ceil(count($messages) / $messagesperpage);
		if (isset($_REQUEST["page"])) {
			$page = intval($_REQUEST["page"]);
		}
		// Vorsichtshalber erlauben wir nur Seiten, auf dennen auch Nachrichten stehen
		if ($page < 0 || $page > $pages) {
			$page = 0;
		}
		
		$this->assign("page", $page);
		$this->assign("pages", $pages);
		$this->assign("board", $this->parseBoard($board));
		$this->assign("thread", $this->parseThread($thread));
		$this->assign("messages", array_slice($messages, $page * $messagesperpage, $messagesperpage));
		$this->assign("mayPost", $mayPost);
		$this->display("viewthread.html.tpl");
		exit;
	}
	
	public function viewmessage($board, $thread, $message, $mayPost = false) {
		// TODO auch auf die richtige seite weiterleiten :o
		header("Location: viewthread.php?boardid={$board->getBoardID()}&threadid={$thread->getThreadID()}#article{$message->getArticleNum()}");
		exit;
	}

	public function viewpostform($board, $reference = null) {
		$subject = "";
		if ($reference !== null) {
			$subject = $reference->getSubject();
			$this->assign("reference", $reference->getMessageID());
			$this->assign("subject", (!in_array(substr($subject,0,3), array("Re:","Aw:")) ? "Re: ".$subject : $subject));
		}

		$this->assign("address", $this->parseAddress($this->getAuth()->getAddress()));
		
		$this->assign("board", $this->parseBoard($board));
		$this->display("postform.html.tpl");
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
