<?php

require_once(dirname(__FILE__) . "/keyvalue.class.php");
require_once(dirname(__FILE__) . "/redis/RedisServer.php");
/* Die Klassen müssen vor dem unserialize eingebunden sein, da PHP sonst
 * incomplete Objekte erstellt.
 * vgl. http://mrfoo.de/archiv/120-The-script-tried-to-execute-a-method-or-access-a-property-of-an-incomplete-object.html
 **/
require_once(dirname(__FILE__)."/../address.class.php");
require_once(dirname(__FILE__)."/../thread.class.php");
require_once(dirname(__FILE__)."/../message.class.php");
require_once(dirname(__FILE__)."/../acknowledge.class.php");
require_once(dirname(__FILE__)."/../cancel.class.php");
require_once(dirname(__FILE__)."/../exceptions/thread.exception.php");
require_once(dirname(__FILE__)."/../exceptions/message.exception.php");

class RedisCacheConnection extends KeyValueCacheConnection {
	private $rediscache;
	private $link;

	public function __construct($rediscache, $uplink) {
		parent::__construct($uplink);
		$this->rediscache = $rediscache;
	}

	private function getLink() {
		if ($this->link === null) {
			$this->link = new RedisServer($this->rediscache->getHost(), $this->rediscache->getPort());
			$this->link->connect($this->rediscache->getHost(), $this->rediscache->getPort());
		}
		return $this->link;
	}

	protected function get($key) {
		return unserialize($this->getLink()->Get($this->rediscache->getKeyName($key)));
	}

	protected function set($key, $val) {
		$this->getLink()->Set($this->rediscache->getKeyName($key), serialize($val));
	}

	protected function delete($key) {
		$this->getLink()->Del($this->rediscache->getKeyName($key));
	}
}

?>
