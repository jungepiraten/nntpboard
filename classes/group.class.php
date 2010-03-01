<?php

require_once(dirname(__FILE__)."/message.class.php");
require_once(dirname(__FILE__)."/thread.class.php");

class Group {
	private $host = "localhost";
	private $group;
	private $username = "";
	private $password = "";

	// Zuordnung MSGID => THREADID
	private $messages = array();
	// Alle Threads als Thread-Objekt (ohne Nachrichten)
	private $threads = array();

	private $threadcache = array();

	public function __construct(Host $host, $group, $username = "", $password = "") {
		$this->host = $host;
		$this->group = $group;
		$this->username = $username;
		$this->password = $password;
	}

	public function setAuth($username, $password) {
		$this->username = $username;
		$this->password = $password;
	}

	public function sendMessage($message) {
		// TODO
	}
	
	/**
	 * TODO: Diese funktionen gehoeren gar nicht hierher (imho)
	 * (evtl auslagern in Config oder neue Klasse)
	 */

	public function getFilename() {
		return dirname(__FILE__)."/../groups/".$this->group.".dat";
	}
	
	public function getThreadfilename($threadid) {
		return dirname(__FILE__)."/../groups/".$this->group."/".md5($threadid).".dat";
	}
	
	public function getAttachmentfilename($part) {
		return dirname(__FILE__)."/../groups/".$this->group."/".md5($part->getMessageID()).".".$part->getPartID().".".$part->getFilename();
	}
	
	public function getAttachmentlink($part) {
		// TODO sieht hoffentlich jeder, oder? ;)
		return "/~prauscher/nntpboard/groups/".$this->group."/".md5($part->getMessageID()).".".$part->getPartID().".".$part->getFilename();
	}
	
	// Lade Zwischenstand
	public function load() {
		$data = unserialize(file_get_contents($this->getFilename()));
		$this->messages	= $data["messages"];
		$this->threads	= $data["threads"];

		/**
		 * Lade Threads erst nach und nach, um Weniger Last zu verursachen
		 * vgl getThread($threadid)
		 **/
	}
	
	// Speichere Zwischenstand
	public function save() {
		$data = array(
			"messages"	=> $this->messages,
			"threads"	=> $this->threads);
		file_put_contents($this->getFilename(), serialize($data));

		// Speichere Threads
		foreach ($this->threadcache AS $threadid => $thread) {
			$filename = $this->getThreadfilename($threadid);
			if (!file_exists(dirname($filename))) {
				mkdir(dirname($filename));
			}
			file_put_contents($filename, serialize($thread));
			
			// Attachments hinterher!
			foreach ($thread AS $message) {
				foreach ($message->getBodyParts() AS $partid => $part) {
					if ($part->isAttachment()) {
						$filename = $this->getAttachmentfilename($part);
						if (!file_exists($filename)) {
							if (!file_exists(dirname($filename))) {
								mkdir(dirname($filename));
							}
							file_put_contents($filename, $part->getText());
						}
					}
				}
			}
		}
	}
	
	// Lade Daten frisch vom Newsserver (ACHTUNG: Das kann eine sehr lange Zeit dauern)
	public function init() {
		$h = @imap_open($this->host->getGroupString($this->group), $this->username, $this->password);
		if ($h === false) {
			throw new Exception(imap_last_error() . " while connecting to {$this->host->getGroupString($this->group)}.");
		}
		
		$c = imap_num_msg($h);
		for ($i = 1; $i <= $c; $i++) {
			$message = $this->parseMessage($h, $i);

			$this->threadcache[$message->getThreadID()][$message->getMessageID()] = $message;

			$this->messages[$message->getMessageID()] = $message->getThreadID();
			// Ist Unterpost
			if ($message->hasParentID()) {
				$this->getMessage($message->getParentID())->addChild($message);
			// Ist Startpost / Threadstarter
			} else {
				$this->threads[$message->getThreadID()] = new Thread($message);
			}
			$this->threads[$message->getThreadID()]->addMessage($message);
		}
		
		// Sortieren
		if (!function_exists("cmpThreads")) {
			function cmpThreads($a, $b) {
				return $b->getLastPostDate() - $a->getLastPostDate();
			}
		}
		uasort($this->threads, cmpThreads);
	}
	
	public function parseMessage($h, $i) {
		$header = imap_header($h, $i);
		$message = new Message($this, $header);
		
		// Strukturanalyse
		$struct = imap_fetchstructure($h, $i);
		if (is_array($struct->parts)) {
			foreach ($struct->parts AS $p => $part) {
				$message->addBodyPart($p, $part, imap_fetchbody($h, $i, $p+1));
			}
		} else {
			$message->addBodyPart(0, $struct, imap_body($h, $i));
		}
		
		return $message;
	}

	public function getThreadCount() {
		return count($this->threads);
	}

	public function getLastPostDate() {
		if (empty($this->threads)) {
			return null;
		}
		return array_shift(array_slice($this->threads, 0, 1))->getLastPostDate();
	}

	public function getLastPostAuthor() {
		if (empty($this->threads)) {
			return null;
		}
		return array_shift(array_slice($this->threads, 0, 1))->getLastPostAuthor();
	}

	public function getThreads() {
		return $this->threads;
	}
	
	public function getThread($threadid) {
		return $this->threads[$threadid];
	}

	public function getThreadMessages($threadid) {
		// Kleines Caching - vermutlich manchmal sinnvoll ;)
		if (!isset($this->threadcache[$threadid])) {
			$this->threadcache[$threadid] = unserialize(file_get_contents($this->getThreadfilename($threadid)));
		}
		return $this->threadcache[$threadid];
	}

	public function getMessage($messageid) {
		// Suche Passende ThreadID
		$thread = $this->getThreadMessages($this->messages[$messageid]);
		return $thread[$messageid];
	}
}

?>
