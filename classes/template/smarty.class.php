<?php

require_once(dirname(__FILE__) . "/../template.class.php");

require_once("/usr/share/php/smarty/libs/Smarty.class.php");

/**
 * NOTE: _all_ view* functions quit execution!
 */

class NNTPBoardSmarty extends AbstractTemplate implements Template {
	private $smarty;
	
	public function __construct($config, $charset, $auth) {
		parent::__construct($config, $charset, $auth);

		$this->smarty = new Smarty;
		$this->smarty->template_dir = dirname(__FILE__) . "/smarty/templates/";
		$this->smarty->compile_dir = dirname(__FILE__) . "/smarty/templates_c/";
		$this->smarty->assign("VERSION", $config->getVersion());
		$this->smarty->assign("CHARSET", $this->getCharset());
		$this->smarty->assign("ISANONYMOUS", $this->getAuth()->isAnonymous());
		$this->smarty->assign("ADDRESS", $this->getAuth()->getAddress());
	}


	private function sendHeaders() {
		header("Content-Type: text/html; Charset={$this->getCharset()}");
	}

	private function parseBoard($board, $parseParent = true) {
		$row = array();
		$row["boardid"]		= $board->getBoardID();
		if ($board->hasParent() && $parseParent == true) {
			$row["parent"]	= $this->parseBoard($board->getParent());
		}
		$row["name"]		= $board->getName();
		$row["desc"]		= $board->getDesc();
		// Letzter Post und ungelesenes Forum markieren
		if ($board->hasThreads()) {
			$c = $board->getConnection($this->getAuth());
			$c->open();
			$group = $c->getGroup();
			$c->close();
			$row["hasthreads"]		= true;
			$row["unread"]			= $this->getAuth()->isUnreadGroup($group);
			$row["threadcount"]		= $group->getThreadCount();
			$row["messagecount"]		= $group->getMessageCount();
			$row["lastpostboardid"]		= $board->getBoardID();
			$row["lastpostsubject"]		= $group->getLastPostSubject($this->getCharset());
			$row["lastpostthreadid"]	= $group->getLastPostThreadID();
			$row["lastpostmessageid"]	= $group->getLastPostMessageID();
			$row["lastpostdate"]		= $group->getLastPostDate();
			$row["lastpostauthor"]		= $this->parseAddress($group->getLastPostAuthor());
		}
		// Unterforen einbinden
		if ($board->hasSubBoards()) {
			$row["childs"]	= array();
			foreach ($board->getSubBoardIDs() AS $childid) {
				// Kleiner Hack, um eine Endlosschleife zu vermeiden
				$child = $this->parseBoard($board->getSubBoard($childid), false);
				$child["parent"] = &$row;
				$row["childs"][] = $child;
				// Markiere auch obere Hierarchien ungelesen
				if ($child["unread"] == true) {
					$row["unread"] = $child["unread"];
				}
				$row["threadcount"]	+= $child["threadcount"];
				$row["messagecount"]	+= $child["messagecount"];
				if (!$board->hasThreads()
				 and $row["lastpostdate"] < $child["lastpostdate"]) {
					$row["lastpostboardid"]   = $child["lastpostboardid"];
					$row["lastpostsubject"]	  = $child["lastpostsubject"];
					$row["lastpostthreadid"]  = $child["lastpostthreadid"];
					$row["lastpostmessageid"] = $child["lastpostmessageid"];
					$row["lastpostdate"]      = $child["lastpostdate"];
					$row["lastpostauthor"]    = $child["lastpostauthor"];
				}
			}
		}
		if (isset($row["parent"])) {
			$row["parent"]["unread"] = $row["unread"];
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
		$row["lastpostauthor"]		= $this->parseAddress($thread->getLastPostAuthor());
		$row["unread"]			= $this->getAuth()->isUnreadThread($thread);
		return $row;
	}

	private function parseMessage($arr) {
		if (is_array($arr)) {
			$message = $arr["message"];
			$acknowledges = $arr["acknowledges"];
		} else {
			$message = $arr;
			$acknowledges = array();
		}
		if ($message == null) {
			return null;
		}
		$row = array();

		$row["messageid"]	= $message->getMessageID();
		$row["subject"]		= $message->getSubject($this->getCharset());
		$row["author"]		= $this->parseAddress($message->getAuthor());
		$row["date"]		= $message->getDate();
		$row["body"]		= $this->formatMessage($message);
		$row["mayCancel"]	= $this->getAuth()->mayCancel($message);
		$row["attachments"]	= array();
		foreach ($message->getAttachments() as $partid => $attachment) {
			$row["attachments"][$partid] = $this->parseAttachment($attachment);
		}
		$row["acknowledges"]	= array();
		$row["nacknowledges"]	= array();
		if (is_array($acknowledges)) {
			$acks = array();
			$ackauthors = array();
			foreach ($acknowledges as $acknowledge) {
				$key = md5($acknowledge->getAuthor());
				$acks[$key] +=		$acknowledge->getWertung();
				$ackauthors[$key] =	$this->parseAddress($acknowledge->getAuthor());
			}
			foreach ($acks as $key => $wertung) {
				$ackrow = array("author" => $ackauthors[$key], "wertung" => $wertung);
				if ($wertung < 0) {
					$row["nacknowledges"][] = $ackrow;
				} elseif ($wertung > 0) {
					$row["acknowledges"][] = $ackrow;
				}
			}
		}
		if ($message->hasSignature()) {
			$row["signature"] = $message->getSignature();
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
		$row = array();
		$row["text"]	= $this->getConfig()->getAddressText($address, $this->getCharset());
		$row["link"]	= $this->getConfig()->getAddressLink($address, $this->getCharset());
		$row["image"]	= $this->getConfig()->getAddressImage($address, $this->getCharset());
		return $row;
	}

	private function formatMessage($message) {
		if ($message->hasHTMLBody()) {
			$text = $message->getHTMLBody($this->getCharset());
			// Nur "gutes" HTML durchlassen
			$text = strip_tags($text, "<b><i><u><a><tt><small><big>");
		} else {
			$text = $message->getTextBody($this->getCharset());
			
			// Inline-GPG ausschneiden (RFC 2440)
			$text = preg_replace('$-----BEGIN PGP SIGNED MESSAGE-----(.*)\r?\n\r?\n(.*)-----BEGIN PGP SIGNATURE-----(.*)-----END PGP SIGNATURE-----$Us', '$2', $text);
			
			// Erkenne auch nicht ausgezeichnete Links
			$text = preg_replace('%([^<]|^)((http|https|ftp|ftps|mailto|xmpp):[^\s>]{6,})([^>]|$)%', '$1<$2>$4', $text);
			
			// htmlentities kommt nur mit wenigen Zeichensaetzen zurecht :(
			$text = iconv("UTF-8", $this->getCharset(),
			              htmlentities(iconv($this->getCharset(), "UTF-8", $text), ENT_COMPAT, "UTF-8") );

			// Zitate sind eine fiese sache ...
			$lines = explode("\n", $text);
			$text = "";
			$quoted = 0;
			for ($i=0; $i<count($lines); $i++) {
				$line = rtrim($lines[$i]);
				$quoted_loc = 0;
				// Wir haben vorher schon htmlentities rausgeparst ...
				while (substr($line,0,4) == "&gt;") {
					$line = ltrim(substr($line,4));
					$quoted_loc++;
				}
				
				if (trim($line) != "") {
					while ($quoted < $quoted_loc) {
						$text .= "<div class=\"quote\">";
						$quoted++;
					}
					while ($quoted > $quoted_loc) {
						$text .= "</div>";
						$quoted--;
					}
				}
				$text .= $line . "\r\n";
			}
			while ($quoted > 0) {
				$text .= "</div>";
				$quoted--;
			}
			// Formatierung
			$text = preg_replace('%(\s|^)(\*[^\s]+\*)(\s|$)%', '$1<b>$2</b>$3', $text);
			$text = preg_replace('%(\s|^)(/[^\s]+/)(\s|$)%', '$1<i>$2</i>$3', $text);
			$text = preg_replace('%(\s|^)(_[^\s]+_)(\s|$)%', '$1<u>$2</u>$3', $text);

			// Links
			$text = preg_replace('%(&lt;)([a-zA-Z]{3,6}:.*)(&gt;)%U', '$1<a href="$2">$2</a>$3', $text);

			// Zeilenumbrueche
			$text = nl2br(trim($text));
		}
		return $text;
	}


	public function viewexception($exception) {
		$this->smarty->assign("message", $exception->getMessage());
		$this->sendHeaders();
		$this->smarty->display("exception.html.tpl");
		exit;
	}
	
	public function viewboard($board, $group, $page = 0, $pages = 0, $threadobjs = null, $mayPost = false, $mayAcknowledge = false) {
		$threads = null;
		if (is_array($threadobjs)) {
			$threads = array();
			foreach ($threadobjs AS $thread) {
				$threads[] = $this->parseThread($thread);
			}
		}
		
		$this->smarty->assign("page", $page);
		$this->smarty->assign("pages", $pages);

		$this->smarty->assign("board", $this->parseBoard($board));
		$this->smarty->assign("threads", $threads);
		$this->smarty->assign("mayPost", $mayPost);
		$this->smarty->assign("mayAcknowledge", $mayAcknowledge);
		$this->sendHeaders();
		$this->smarty->display("viewboard.html.tpl");
		exit;
	}
	
	public function viewthread($board, $thread, $page, $pages, $messagesobjs, $mayPost = false, $mayAcknowledge = false) {
		$messages = array();
		if (is_array($messagesobjs)) {
			foreach ($messagesobjs as $message) {
				$messages[] = $this->parseMessage($message);
			}
		}
		
		$this->smarty->assign("page", $page);
		$this->smarty->assign("pages", $pages);
		
		$this->smarty->assign("board", $this->parseBoard($board));
		$this->smarty->assign("thread", $this->parseThread($thread));
		$this->smarty->assign("messages", $messages);
		$this->smarty->assign("mayPost", $mayPost);
		$this->smarty->assign("mayAcknowledge", $mayAcknowledge);
		$this->sendHeaders();
		$this->smarty->display("viewthread.html.tpl");
		exit;
	}
	
	public function viewmessage($board, $thread, $message, $mayPost = false, $mayAcknowledge = false) {
		$page = floor($thread->getMessagePosition($message) / $this->getConfig()->getMessagesPerPage());
		$location = "viewthread.php?boardid=" . urlencode($board->getBoardID()) .
		            "&threadid=" . urlencode($thread->getThreadID()) .
		            "&page=" . intval($page) . "#article" . 
		            urlencode($message->getMessageID());
		header("Location: " . $location);
		$this->sendHeaders();
		echo "<a href=\"".htmlentities($location)."\">Weiter</a>";
		exit;
	}

	public function viewpostform($board, $reference = null, $quote = false, $preview = null) {
		$subject = "";
		if ($reference !== null) {
			$subject = $reference->getSubject();
			$this->smarty->assign("subject", (!in_array(substr($subject,0,3), array("Re:","Aw:")) ? "Re: ".$subject : $subject));
			$this->smarty->assign("reference", $reference->getMessageID());
			if ($quote == true) {
				$body  = $this->getConfig()->getAddressText($reference->getAuthor(), $this->getCharset()) . " schrieb:" . "\r\n";
				$lines = explode("\n", $reference->getTextBody());
				foreach ($lines as $line) {
					$body .= "> " . rtrim($line) . "\r\n";
				}
				$this->smarty->assign("body", $body);
			}
		}

		$this->smarty->assign("address", $this->parseAddress($this->getAuth()->getAddress()));
		
		$this->smarty->assign("board", $this->parseBoard($board));
		$this->smarty->assign("preview", $this->parseMessage($preview));
		$this->sendHeaders();
		$this->smarty->display("postform.html.tpl");
		exit;
	}

	public function viewpostsuccess($board, $thread, $message) {
		$this->sendHeaders();
		$this->viewmessage($board, $thread, $message, true);
		exit;
	}

	public function viewpostmoderated($board, $thread, $message) {
		$this->smarty->assign("board", $this->parseBoard($board));
		$this->smarty->assign("message", $this->parseMessage($message));
		$this->sendHeaders();
		$this->smarty->display("postmoderated.html.tpl");
		exit;
	}

	public function viewacknowledgesuccess($board, $thread, $message, $ack) {
		$this->sendHeaders();
		$this->viewmessage($board, $thread, $message, true);
		exit;
	}

	public function viewacknowledgemoderated($board, $thread, $message, $ack) {
		$this->smarty->assign("board", $this->parseBoard($board));
		$this->smarty->assign("message", $this->parseMessage($message));
		$this->sendHeaders();
		$this->smarty->display("postmoderated.html.tpl");
		exit;
	}

	public function viewcancelsuccess($board, $thread, $message, $cancel) {
		$this->sendHeaders();
		$this->viewmessage($board, $thread, $message, true);
		exit;
	}

	public function viewcancelmoderated($board, $thread, $message, $cancel) {
		$this->smarty->assign("board", $this->parseBoard($board));
		$this->smarty->assign("message", $this->parseMessage($message));
		$this->sendHeaders();
		$this->smarty->display("postmoderated.html.tpl");
		exit;
	}
	
	public function viewloginform() {
		$this->smarty->assign("referer", $_SERVER["HTTP_REFERER"]);
		$this->sendHeaders();
		$this->smarty->display("loginform.html.tpl");
		exit;
	}

	public function viewloginfailed() {
		$this->smarty->assign("loginfailed", true);
		$this->sendHeaders();
		$this->smarty->display("loginform.html.tpl");
		exit;
	}

	public function viewloginsuccess($auth) {
		// Da sich nach einem erfolgreichen Login das Authobjekt geaendert hat, updaten wir hier mal schnell
		$this->smarty->assign("auth", $auth);
		
		if (isset($_REQUEST["redirect"])) {
			header("Location: " . stripslashes($_REQUEST["redirect"]));
		} elseif (isset($_SERVER["HTTP_REFERER"])) {
			header("Location: " . stripslashes($_SERVER["HTTP_REFERER"]));
		} else {
			header("Location: index.php");
		}
		$this->sendHeaders();
		exit;
	}

	public function viewlogoutsuccess() {
		// Da sich nach einem erfolgreichen Logout das Authobjekt geaendert hat, updaten wir hier mal schnell
		$this->smarty->assign("auth", null);
		
		if (isset($_REQUEST["redirect"])) {
			header("Location: " . stripslashes($_REQUEST["redirect"]));
		} elseif (isset($_SERVER["HTTP_REFERER"])) {
			header("Location: " . stripslashes($_SERVER["HTTP_REFERER"]));
		} else {
			header("Location: index.php");
		}
		$this->sendHeaders();
		exit;
	}
	
	public function viewuserpanel() {
		$this->sendHeaders();
		$this->smarty->display("userpanel.html.tpl");
		exit;
	}
}

?>
