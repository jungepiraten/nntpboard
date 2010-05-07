<?php

require_once(dirname(__FILE__) . "/../connection.class.php");

class UplinkConnection extends AbstractConnection {
	private $threads = null;
	private $threadids = array();

	private $uplink;

	public function __construct($uplink) {
		$this->uplink = $uplink;
	}
	
	public function open() {
		$this->uplink->open();
	}

	public function close() {
		$this->uplink->close();
	}

	public function getMessageIDs() {
		return $this->uplink->getMessageIDs();
	}

	public function getMessageCount() {
		return $this->uplink->getMessageCount();
	}

	public function hasMessage($msgid) {
		return $this->uplink->hasMessage($msgid);
	}

	public function getMessage($msgid) {
		return $this->uplink->getMessage($msgid);
	}

	public function post($message) {
		return $this->uplink->post($message);
	}

	protected function getLastThread() {
		// Initialisiere die Threads, falls noch nicht geschehen
		if (!isset($this->threads)) {
			$this->initThreads();
		}
		// Wenn wir noch immer keine Threads finden koennen, haben wir wohl keine :(
		if (empty($this->threads)) {
			throw new EmptyGroupException($this->group);
		}
		// Wir nehmen an, dass die Threads sortiert sind ...
		return array_shift(array_slice($this->threads, 0, 1));
	}


	public function getThreadIDs() {
		if (!isset($this->threads)) {
			$this->initThreads();
		}
		return array_keys($this->threads);
	}

	public function getThreadCount() {
		if (!isset($this->threads)) {
			$this->initThreads();
		}
		return count($this->threads);
	}

	public function hasThread($messageid) {
		// Wenn die Threads noch nicht initalisiert sind, nehmen wir an,
		// dass wir diesen Thread nicht haben
		if (!isset($this->threads)) {
			return false;
		}
		return isset($this->threads[$this->threadids[$messageid]]);
	}

	public function getThread($messageid) {
		if (!isset($this->threads)) {
			$this->initThreads();
		}
		return $this->threads[$this->threadids[$messageid]];
	}

	/**
	 * Initialisiere den Thread-Array
	 * Dafuer muessen wir ALLE nachrichten laden und sortieren :(
	 * TODO brauchen wir hier ueberhaupt Threads? - Umstrukturieren: Uplink & UplinkConnection!
	 **/
	private function initThreads() {
		$this->threads = array();
		foreach ($this->getMessageIDs() AS $msgid) {
			$message = $this->getMessage($msgid);

			// Entweder Unterpost oder neuen Thread starten
			if ($message->hasParent() && $this->hasMessage($message->getParentID())) {
				$this->getMessage($message->getParentID())->addChild($message);
			}
			
			if ($message->hasParent() && $this->hasThread($message->getParentID())) {
				$thread = $this->getThread($message->getParentID());
			} else {
				$thread = new Thread($message);
				$this->threads[$thread->getThreadID()] = $thread;
			}

			// Nachricht zum Thread hinzufuegen
			$thread->addMessage($message);
			$this->threadids[$message->getMessageID()] = $thread->getThreadID();
		}
		// Threads sortieren
		$this->sort();
	}
	
	/**
	 * Sortiere die Threads nach Letztem Posting (in der Uebersicht wichtig)
	 * TODO sortieren umlagern?
	 **/
	public function sort() {
		if (!function_exists("cmpThreads")) {
			function cmpThreads($a, $b) {
				return $b->getLastPostDate() - $a->getLastPostDate();
			}
		}
		uasort($this->threads, cmpThreads);
	}
}

?>
