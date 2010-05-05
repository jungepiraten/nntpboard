<?php

require_once(dirname(__FILE__)."/config.class.php");
require_once(dirname(__FILE__)."/connection/cache.class.php");
require_once(dirname(__FILE__)."/connection/cacheprovider/file.class.php");
require_once(dirname(__FILE__)."/connection/nntp.class.php");

class Group {
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
	const POSTMODE_MODERATED_AUTH =	4;
	/* Keiner darf schreiben */
	const POSTMODE_CLOSED =	5;
	
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
	
	public function getConnection($auth = null) {
		// Frage die Gruppen-Spezifischen Eigenschaften ab
		switch ($this->cachemode) {
		case self::CACHEMODE_NOCACHE:
			return $this->getDirectConnection($auth);
		case self::CACHEMODE_READONLY:
			return $this->getMixedConnection($auth);
		case self::CACHEMODE_CACHEONLY:
			return $this->getCacheConnection($auth);
		}

		throw new Exception("Ungueltiger CacheMode!");
	}

	private function getMixedConnection($auth) {
		return new CacheConnection($this, $auth, $this->getCacheProvider(),
		           $this->getDirectConnection($auth));
	}

	private function getDirectConnection($auth) {
		return new NNTPConnection($this, $auth);
	}

	private function getCacheConnection($auth) {
		return new CacheConnection($this, $auth, $this->getCacheProvider());
	}

	private function getCacheProvider() {
		return new FileCacheProvider(dirname(__FILE__) . "/../data/" . $this->getGroup());
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
}

?>
