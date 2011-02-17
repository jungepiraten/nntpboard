<?php

require_once(dirname(__FILE__)."/classes/host.class.php");
require_once(dirname(__FILE__)."/classes/memcachehost.class.php");
require_once(dirname(__FILE__)."/classes/config.class.php");
require_once(dirname(__FILE__)."/classes/board.class.php");
require_once(dirname(__FILE__)."/classes/board/filecachednntp.class.php");
require_once(dirname(__FILE__)."/classes/board/memcachednntp.class.php");

require_once(dirname(__FILE__)."/classes/auth/jupis.class.php");
require_once(dirname(__FILE__)."/classes/template/smarty.class.php");

class TestConfig extends DefaultConfig {
	private $secretkey;

	public function __construct($secretkey) {
		parent::__construct();
		$this->addBoard(new Board(null, null, "Testboards", ""));

		$host = new Host("prauscher.homeip.net");
		$memcache = new MemcacheHost("localhost", 11211, "nntpboard999");

		$this->addBoard(new Board(900, null, "Boards", "Unterforen"));
		$this->addBoard(new FileCachedNNTPBoard(998, 900, "eins", "A",
				false, true, true, $host, "prauscher.test"));
		$this->addBoard(new MemCachedNNTPBoard(999, 900, "zwei", "B",
				false, true, false, $memcache, $host, "prauscher.testing"));

		$this->secretkey = $secretkey;
	}
	
	public function getTemplate($auth) {
		return new NNTPBoardSmarty($this, $this->getCharset(), $auth);
	}

	public function getAuth($user, $pass) {
		return JuPisAuth::authenticate($user, $pass);
	}

	public function getAnonymousAuth() {
		return new JuPisAnonAuth();
	}

	public function getAddressText($address, $charset) {
		$mailto = iconv($address->getCharset(), $charset, $address->getAddress());
		list($name, $host) = explode("@", $mailto);
		if ($host == "auth.invalid") {
			return ucfirst($name);
		}
		return parent::getAddressText($address, $charset);
	}

	public function getMessageIDHost() {
		return "webnntp.prauscher.homelinux.net";
	}

	protected function getSecretKey() {
		return $this->secretkey;
	}
}

?>
