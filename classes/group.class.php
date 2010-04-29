<?php

require_once(dirname(__FILE__)."/config.class.php");
require_once(dirname(__FILE__)."/connection/mixed.class.php");
require_once(dirname(__FILE__)."/connection/cache.class.php");
require_once(dirname(__FILE__)."/connection/nntp.class.php");

class Group {
	const CONNECTION_DEFAULT =	1;
	const CONNECTION_DIRECT =	2;
	const CONNECTION_CACHE =	3;

	/* Immer direkte Verbindung nutzen */
	const CACHEMODE_NOCACHE =	1;
	/* Cache nur zum Lesen benutzen */
	const CACHEMODE_READONLY =	2;
	/* Immer Cache benutzen */
	const CACHEMODE_CACHEONLY =	3;

	/* Alle duerfen lesen */
	const READMODE_OPEN =		1;
	/* Authentifizierte Benutzer duerfen lesen */
	const READMODE_AUTH =		2;
	/* Keiner darf lesen */	
	const READMODE_CLOSED =		3;

	/* Alle duerfen schreiben */
	const POSTMODE_OPEN =	1;
	/* Authentifizierte Benutzer duerfen schreiben */
	const POSTMODE_AUTH =	2;
	/* Moderierte Gruppe, Alle duerfen vorschlagen */
	const POSTMODE_MODERATED_OPEN =	3;
	/* Moderierte Gruppe, Authentifizierte Benutzer duerfen vorschlagen */
	const POSTMODE_MODERATED_AUTH =	3;
	/* Keiner darf schreiben */
	const POSTMODE_CLOSED =	3;
	
	private $host;
	private $group;
	private $readmode;
	private $postmode;
	private $cachemode;

	public function __construct(Host $host, $group, $readmode = self::READMODE_OPEN, $postmode = self::POSTMODE_AUTH, $cachemode = self::CACHEMODE_READONLY) {
		$this->host = $host;
		$this->group = $group;
		
		$this->readmode = $readmode;
		$this->postmode = $postmode;
		$this->cachemode = $cachemode;
	}
	
	public function getHost() {
		return $this->host;
	}
	
	public function getGroup() {
		return $this->group;
	}
	
	public function getConnection($datadir = null, $auth = null, $useconnection = self::CONNECTION_DEFAULT) {
		// Ist dies ein Spezialfall?
		switch ($useconnection) {
		case self::CONNECTION_DIRECT:
			return $this->getDirectConnection($datadir, $auth);
		case self::CONNECTION_CACHE:
			return $this->getCacheConnection($datadir, $auth);
		}
		
		// Frage die Gruppen-Spezifischen Eigenschaften ab
		switch ($this->cachemode) {
		case self::CACHEMODE_NOCACHE:
			return $this->getDirectConnection($datadir, $auth);
		case self::CACHEMODE_READONLY:
			return $this->getMixedConnection($datadir, $auth);
		case self::CACHEMODE_CACHEONLY:
			return $this->getCacheConnection($datadir, $auth);
		}

		throw new Exception("Ungueltiger CacheMode!");
	}

	public function mayRead($auth) {
		switch ($this->readmode) {
		case self::READMODE_OPEN:
			return true;
		case self::READMODE_AUTH:
			return ($auth != null && !$auth->isAnonymous());
		case self::READMODE_CLOSED:
			return false;
		}

		throw new Exception("Ungueltiger ReadMode!");
	}

	public function mayPost($auth) {
		switch ($this->postmode) {
		case self::POSTMODE_OPEN:
		case self::POSTMODE_MODERATED_OPEN:
			return true;
		case self::POSTMODE_AUTH:
		case self::POSTMODE_MODERATED_AUTH:
			return ($auth != null && !$auth->isAnonymous());
		case self::POSTMODE_CLOSED:
			return false;
		}

		throw new Exception("Ungueltiger PostMode!");
	}

	public function isModerated() {
		switch ($this->postmode) {
		case self::POSTMODE_MODERATED_OPEN:
		case self::POSTMODE_MODERATED_AUTH:
			return true;
		}
		return false;
	}

	private function getMixedConnection($datadir, $auth) {
		$connection = new MixedConnection($this->getCacheConnection($datadir, $auth));
		$connection->addConnection( MixedConnection::USE_POST, $this->getDirectConnection($datadir, $auth) );
		return $connection;
	}

	private function getDirectConnection($datadir, $auth) {
		return new NNTPConnection($this, $auth);
	}

	private function getCacheConnection($datadir, $auth) {
		return new CacheConnection($this, $auth, $datadir);
	}
}

?>
