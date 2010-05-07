<?php

require_once(dirname(__FILE__)."/../group.class.php");
require_once(dirname(__FILE__)."/../connection/cacheprovider/file.class.php");
require_once(dirname(__FILE__)."/../uplinks/nntp.class.php");

class NNTPGroup extends AbstractGroup {
	private $host;
	private $group;

	public function __construct(Host $host, $group, $readmode = self::READMODE_OPEN, $postmode = self::POSTMODE_AUTH, $cachemode = self::CACHEMODE_READONLY) {
		parent::__construct($readmode, $postmode, $cachemode);

		$this->host = $host;
		$this->group = $group;
	}
	
	public function getHost() {
		return $this->host;
	}
	
	public function getGroup() {
		return $this->group;
	}

	protected function getCacheProvider() {
		return new FileCacheProvider(dirname(__FILE__) . "/../../data/nntp_" . $this->getGroup());
	}

	protected function getUplink($auth) {
		return new NNTPUplink($this, $auth);
	}

}

?>
