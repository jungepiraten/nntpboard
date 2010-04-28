<?php

require_once(dirname(__FILE__)."/config.class.php");
require_once(dirname(__FILE__)."/connection/mixed.class.php");
require_once(dirname(__FILE__)."/connection/cache.class.php");
require_once(dirname(__FILE__)."/connection/nntp.class.php");

class Group {
	const CONNECTION_DEFAULT = 1;
	const CONNECTION_DIRECT =  2;
	const CONNECTION_CACHE =   3;

	/* Immer direkte Verbindung nutzen */
	const CACHEMODE_NOCACHE =   1;
	/* Cache nur zum Lesen benutzen */
	const CACHEMODE_READONLY =  2;
	/* Immer Cache benutzen */
	const CACHEMODE_CACHEONLY = 3;
	
	private $host;
	private $group;
	private $cachemode;

	public function __construct(Host $host, $group, $cachemode = self::CACHEMODE_READONLY) {
		$this->host = $host;
		$this->group = $group;
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
			return $this->getDirectConnection($auth);
		case self::CONNECTION_CACHE:
			return $this->getCacheConnection($datadir);
		}
		
		// Frage die Gruppen-Spezifischen Eigenschaften ab
		switch ($this->cachemode) {
		case self::CACHEMODE_NOCACHE:
			return $this->getDirectConnection($auth);
		case self::CACHEMODE_READONLY:
			$connection = new MixedConnection( $this->getCacheConnection($datadir) );
			$connection->addConnection( MixedConnection::USE_POST, $this->getDirectConnection($auth) );
			return $connection;
		case self::CACHEMODE_CACHEONLY:
			return $this->getCacheConnection($datadir);
		}

		throw new Exception("Ungueltiger CacheMode!");
	}

	private function getDirectConnection($auth) {
		return new NNTPConnection($this, $auth);
	}

	private function getCacheConnection($datadir) {
		$readonlycache = in_array($this->cachemode, array(self::CACHEMODE_NOCACHE, self::CACHEMODE_READONLY));
		return new CacheConnection($this, $datadir, $readonlycache);
	}
}

?>
