<?php

require_once(dirname(__FILE__) . "/../template.class.php");

require_once("Smarty/Smarty.class.php");

function parseFacEs($text, $host, &$cache) {
	if (!isset($cache[$host])) {
		$cache[$host] = array();
	}
	preg_match_all('#' . preg_quote($host) . '/([0-9]+)#', $text, $matches, PREG_SET_ORDER);
	foreach ($matches as $match) {
		if (!isset($cache[$host][$match[1]])) {
			$item = array_pop(json_decode(file_get_contents("http://" . $host . "/api/id/" . $match[1]))->items);
			if (!empty($item->face_thumbnail)) {
				$cache[$host][$match[1]] = $item->face_thumbnail;
			} else {
				$cache[$host][$match[1]] = $item->face_url;
			}
		}
	}
	foreach ($cache[$host] as $id => $thumb) {
		$text = preg_replace('#(&lt;|<|)(http://|https://|)' . $host . "/" . preg_quote($id) . '(&gt;|>|)#', '<a href="http://' . $host . "/" . $id . '"><img src="' . $thumb . '" width=100 /></a>', $text);
	}
	return $text;
}

/**
 * NOTE: _all_ view* functions quit execution!
 */

class NNTPBoardSmarty extends AbstractTemplate implements Template {
	private $smarty;

	// Used for ragefac.es, ponyfac.es, lauerfac.es
	private $apiCache;
	
	public function __construct($config, $charset, $auth) {
		parent::__construct($config, $charset, $auth);

		$this->smarty = new Smarty;
		$this->smarty->template_dir = dirname(__FILE__) . "/smarty/templates/";
		$this->smarty->compile_dir = dirname(__FILE__) . "/smarty/templates_c/";
		$this->smarty->register_modifier("encodeMessageID", array($config, "encodeMessageID"));
		$this->smarty->assign("ROOTBOARD", $this->parseBoard($config->getBoard()));
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
		$row["unread"]		= false;
		$row["threadcount"]	= 0;
		$row["messagecount"]	= 0;
		$row["lastpostdate"]	= 0;
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
			$row["signature"] = $this->formatText($message->getSignature());
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
		$text = $message->getHTMLBody($this->getCharset());
		if ($message->hasHTMLBody() and $text == strip_tags($text, "<b><i><u><a><tt><small><big>")) {
			return $text;
		}
		
		$text = $message->getTextBody($this->getCharset());
		$text = $this->formatText($text);
		return $text;
	}

	private function formatText($text) {
		$tid = md5($text);

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
		$quotestack = array();
		$quotes = array();
		for ($i=0; $i<count($lines) + 1; $i++) {
			$quoted_loc = 0;
			$line = "";
			if ($i < count($lines)) {
				$line = rtrim($lines[$i]);
				// Wir haben vorher schon htmlentities rausgeparst ...
				while (substr($line,0,5) == "&gt; " ||
				       substr($line,0,5) == "&gt;\n" ||
				       substr($line,0,8) == "&gt;&gt;") {
					$line = ltrim(substr($line,4));
					$quoted_loc++;
				}
			}

			while ($quoted < $quoted_loc) {
				$qid = $tid . "-" . count($quotestack) . "-" . $i;
				array_unshift($quotestack, $qid);
				$quotes[$qid] = "";
				$quoted++;
			}
			while ($quoted > $quoted_loc) {
				$qid = array_shift($quotestack);
				$quotetext = $quotes[$qid];
				$q = "";
				if (count(explode("\n", $quotetext)) <= 7) {
					$q .= "<a class=\"quotetoggle\" style=\"display:block;\" href=\"javascript:toggleQuote('{$qid}')\" id=\"quotelink{$qid}\">Zitat verstecken</a>";
					$q .= "<blockquote class=\"quote\" id=\"quote{$qid}\">";
				} else {
					$q .= "<a class=\"quotetoggle\" style=\"display:block;\" href=\"javascript:toggleQuote('{$qid}')\" id=\"quotelink{$qid}\">Zitat anzeigen</a>";
					$q .= "<blockquote class=\"quote\" id=\"quote{$qid}\" style=\"display:none;\">";
				}
				$q .= $quotetext;
				$q .= "</blockquote>";
				if (count($quotestack) > 0) {
					$quotes[reset($quotestack)] .= $q;
				} else {
					$text .= $q;
				}
				$quoted--;
			}
			if (count($quotestack) > 0) {
				$quotes[reset($quotestack)] .= $line . "\r\n";
			} else {
				$text .= $line . "\r\n";
			}
		}

		// Formatierung
		$text = preg_replace('%(\s|^)(\*[^\s]+\*)(\s|$)%', '$1<b>$2</b>$3', $text);
		$text = preg_replace('%(\s|^)(/[^\s]+/)(\s|$)%', '$1<i>$2</i>$3', $text);
		$text = preg_replace('%(\s|^)(_[^\s]+_)(\s|$)%', '$1<u>$2</u>$3', $text);

		// ragefac.es
		$text = parseFacEs($text, "ragefac.es", $this->apiCache);
		$text = parseFacEs($text, "ponyfac.es", $this->apiCache);
		$text = parseFacEs($text, "lauerfac.es", $this->apiCache);

		// Links
		$text = preg_replace('%(&lt;)([a-zA-Z]{3,6}:[^ ]*)(&gt;)%U', '<a href="$2">$2</a>', $text);

		// Bei nicht-angemeldeten Benutzern versuchen, Mailadressen zu filtern
		if ($this->getAuth() == null || $this->getAuth()->isAnonymous()) {
			preg_match_all('/(&lt;)?([^\\s]{3,}@[^\\s:]{3,})(&gt;)?/', $text, $matches);
			foreach ($matches[0] as $mail) {
				if (substr($mail, 0, 4) == "&lt;") {
					$mail = substr($mail, 4);
				}
				if (substr($mail, -4) == "&gt;") {
					$mail = substr($mail, 0, strlen($mail) - 4);
				}
				$address = new Address("", $mail);
				$html = '<a href="' . htmlentities($this->getConfig()->getAddressLink($address, $this->getCharset())) . '">' . htmlentities($this->getConfig()->getAddressText($address, $this->getCharset())) . '</a>';
				$text = str_replace("&lt;" . $mail . "&gt;", $html, $text);
				$text = str_replace($mail, $html, $text);
			}
		}
		
		// Zeilenumbrueche
		$text = nl2br(trim($text));
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
		            "&threadid=" . urlencode($this->getConfig()->encodeMessageID($thread->getThreadID())) .
		            "&page=" . intval($page) . "#article" . 
		            urlencode($this->getConfig()->encodeMessageID($message->getMessageID()));
		header("Location: " . $location);
		$this->sendHeaders();
		echo "<a href=\"".htmlentities($location)."\">Weiter</a>";
		exit;
	}

	public function viewpostform($board, $maxuploadsize, $referencemessages = null, $reference = null, $quote = false, $preview = null, $attachmentobjects = null) {
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
		if ($referencemessages !== null) {
			$messages = array();
			foreach ($referencemessages as $message) {
				$messages[] = $this->parseMessage($message);
			}
			$this->smarty->assign("referencemessages", $messages);
		}

		$attachments = array();
		if (is_array($attachmentobjects)) {
			foreach ($attachmentobjects as $o) {
				$attachments[] = $this->parseAttachment($o);
			}
		}

		$this->smarty->assign("address", $this->parseAddress($this->getAuth()->getAddress()));
		
		$this->smarty->assign("board", $this->parseBoard($board));
		$this->smarty->assign("maxuploadsize", $maxuploadsize);
		$this->smarty->assign("attachments", $attachments);
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
		$this->smarty->assign("referer", isset($_REQUEST["referer"]) ? $_REQUEST["referer"] : (isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "index.php"));
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
		
		if (!empty($_REQUEST["redirect"])) {
			header("Location: " . stripslashes($_REQUEST["redirect"]));
		} elseif (!empty($_SERVER["HTTP_REFERER"])) {
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
		
		if (!empty($_REQUEST["redirect"])) {
			header("Location: " . stripslashes($_REQUEST["redirect"]));
		} elseif (!empty($_SERVER["HTTP_REFERER"])) {
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
