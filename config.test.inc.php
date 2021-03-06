<?php

require_once(dirname(__FILE__)."/classes/host.class.php");
require_once(dirname(__FILE__)."/classes/memcachehost.class.php");
require_once(dirname(__FILE__)."/classes/redishost.class.php");
require_once(dirname(__FILE__)."/classes/indexer/mysql.class.php");
require_once(dirname(__FILE__)."/classes/config.class.php");
require_once(dirname(__FILE__)."/classes/board.class.php");
require_once(dirname(__FILE__)."/classes/board/filecachednntp.class.php");
require_once(dirname(__FILE__)."/classes/board/memcachednntp.class.php");
require_once(dirname(__FILE__)."/classes/board/memcachedimap.class.php");
require_once(dirname(__FILE__)."/classes/board/rediscachedimap.class.php");

require_once(dirname(__FILE__)."/classes/authmanager/static.class.php");
require_once(dirname(__FILE__)."/classes/auth/anon.class.php");
require_once(dirname(__FILE__)."/classes/auth/file.class.php");
require_once(dirname(__FILE__)."/classes/template/smarty.class.php");

class TestConfig extends DefaultConfig {
	private $secretkey;

	public function __construct($secretkey) {
		parent::__construct();
		$this->addBoard(new Board(null, null, "Testboards", "", null, new StaticAuthManager(true)));

		$host = new Host("localhost");
		$memcache = new MemcacheHost("localhost", 11211, "nntpboard999");
		$indexer = $this->getIndexer();

		$this->addBoard(new Board(900, null, "Boards", "Unterforen", new StaticAuthManager(true), new StaticAuthManager(true)));
		$this->addBoard(new FileCachedNNTPBoard(998, 900, "eins", "A", $indexer,
				new StaticAuthManager(true), new StaticAuthManager(true), true, $host, "test.a"));
		$this->addBoard(new MemCachedNNTPBoard(999, 900, "zwei", "B", $indexer,
				new StaticAuthManager(true), new StaticAuthManager(true), false, $memcache, $host, "test.b"));

		$this->addBoard(new MemCachedIMAPBoard(1000, 900, "imap", "Z", $indexer,
				new StaticAuthManager(true), new StaticAuthManager(true), false, new MemCacheHost("localhost", 11211, "nntpboard1000"), new Host("localhost", 143), "prauscher@example.net", "", "INBOX"));
		$this->addBoard(new RedisCachedIMAPBoard(1001, 900, "imap2", "Y", $indexer,
				new StaticAuthManager(true), new StaticAuthManager(true), false, new RedisHost("localhost", 6379, "nntpboard1001"), new Host("localhost", 143), "prauscher@example.net", "", "INBOX"));

		$this->secretkey = $secretkey;
	}

	public function getIndexer() {
		return new MySqlIndexer("localhost", "root", "anything92", "nntpboard");
	}

	public function getTemplate($auth) {
		return new NNTPBoardSmarty($this, $auth);
	}

	public function getAuth($user, $pass) {
		return new FileAuth($user, $pass, $user . "@unread", $user, $pass);
	}

	public function getAnonymousAuth() {
		return new AnonAuth();
	}

	public function getAddressText($address) {
		$mailto = $address->getAddress();
		list($name, $host) = explode("@", $mailto);
		if ($host == "auth.invalid") {
			return ucfirst($name);
		}
		return parent::getAddressText($address);
	}

	public function getMessageIDHost() {
		return "webnntp.prauscher.homelinux.net";
	}

	protected function getSecretKey() {
		return $this->secretkey;
	}
}

?>
