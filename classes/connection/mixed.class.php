<?php

require_once(dirname(__FILE__) . "/../connection.class.php");

class MixedConnection extends AbstractConnection {
	const USE_DEFAULT = 0;
	const USE_READ = 1;
	const USE_POST = 2;

	private $openconnections = array();
	private $connections = array();
	
	public function __construct($defaultconnection) {
		parent::__construct();
		$this->addConnection(self::USE_DEFAULT, $defaultconnection);
	}
	
	public function addConnection($use, $connection) {
		$this->connections[$use] = $connection;
	}

	private function getConnection($use) {
		if (isset($this->connections[$use])) {
			$connection = $this->connections[$use];
			// Verbindung oeffnen, falls noch nicht geschehen
			if (!in_array($use, $this->openconnections)) {
				$connection->open();
				$this->openconnections[] = $use;
			}
			return $connection;
		}
		if ($use != self::USE_DEFAULT) {
			return $this->getConnection(self::USE_DEFAULT);
		}
		return null;
	}
	
	/** Wir oeffnen die Verbindung bei bedarf **/
	public function open() {}
	public function close() {
		// Verbindungen schliessen und gleichzeitig aus dem Array loeschen
		while (count($this->openconnections) > 0) {
			$use = array_shift($this->openconnections);
			$this->connections[$use]->close();
		}
	}

	public function getThreadCount() {
		return $this->getConnection(self::USE_READ)->getThreadCount();
	}
	public function getMessagesCount() {
		return $this->getConnection(self::USE_READ)->getMessagesCount();
	}

	public function getThreads() {
		return $this->getConnection(self::USE_READ)->getThreads();
	}
	public function getArticleNums() {
		return $this->getConnection(self::USE_READ)->getArticleNums();
	}
	public function hasMessageNum($num) {
		return $this->getConnection(self::USE_READ)->hasMessageNum($num);
	}
	public function getMessageByNum($num) {
		return $this->getConnection(self::USE_READ)->getMessageByNum($num);
	}
	public function hasMessage($msgid) {
		return $this->getConnection(self::USE_READ)->hasMessage($msgid);
	}
	public function getMessage($msgid) {
		return $this->getConnection(self::USE_READ)->getMessage($msgid);
	}
	public function hasThread($threadid) {
		return $this->getConnection(self::USE_READ)->hasThread($threadid);
	}
	public function getThread($threadid) {
		return $this->getConnection(self::USE_READ)->getThread($threadid);
	}
	
	protected function getLastThread() {
		return $this->getConnection(self::USE_READ)->getLastThread();
	}

	public function post($message) {
		// Mache die eigentliche Post-Leitung zuerst, um Sync-Fehler zu verhindern
		$ret = $this->getConnection(self::USE_POST)->post($message);
		foreach (array_keys($this->connections) AS $use) {
			// USE_POST haben wir ja schon gemacht
			if ($use != self::USE_POST) {
				$connection = $this->getConnection($use);
				$connection->post($message);
			}
		}
		return $ret;
	}
}

?>
