<?php

require_once(dirname(__FILE__)."/connection/cache.class.php");
require_once(dirname(__FILE__)."/connection/uplink.class.php");

interface Group {
	public function mayRead($auth);
	public function mayPost($auth);
	public function isModerated();
	public function getConnection($auth);
}

abstract class AbstractGroup implements Group {
	// TODO unschoen - aber noch mehr klassen nutzen? ...
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
	
	private $readmode;
	private $postmode;
	private $cachemode;

	public function __construct($readmode = self::READMODE_OPEN, $postmode = self::POSTMODE_AUTH, $cachemode = self::CACHEMODE_READONLY) {
		$this->readmode = $readmode;
		$this->postmode = $postmode;
		$this->cachemode = $cachemode;
	}

	abstract protected function getCacheProvider();

	abstract protected function getUplink($auth);

	protected function getCacheConnection($auth) {
		return new CacheConnection($this, $this->getCacheProvider());
	}

	protected function getMixedConnection($auth) {
		return new CacheConnection($this, $this->getCacheProvider(), $this->getUplink($auth));
	}

	protected function getDirectConnection($auth) {
		return new UplinkConnection($this->getUplink($auth));
	}
	
	public function getConnection($auth) {
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
