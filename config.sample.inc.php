<?php

require_once(dirname(__FILE__)."/classes/host.class.php");
require_once(dirname(__FILE__)."/classes/config.class.php");
require_once(dirname(__FILE__)."/classes/board.class.php");
require_once(dirname(__FILE__)."/classes/board/filecachednntp.class.php");
require_once(dirname(__FILE__)."/classes/board/memcachednntp.class.php");

require_once(dirname(__FILE__)."/classes/template/smarty.class.php");

class SampleConfig extends DefaultConfig {
	private $secretkey;

	public function __construct($secretkey) {
		parent::__construct();
		$this->addBoard(new Board(null, null, "Example", ""));

		$host = new Host("news.example.net");

		$this->addBoard(new FileCachedNNTPBoard(998, 900, "eins", "A",
				false, true, true, $host, "prauscher.test"));
		$this->addBoard(new MemCachedNNTPBoard(999, 900, "zwei", "B",
				false, true, false, $host, "prauscher.testing"));

		$this->secretkey = $secretkey;
	}
	
	public function getTemplate($auth) {
		return new NNTPBoardSmarty($this, $this->getCharset(), $auth);
	}

	public function getAuth($user, $pass) {
		return null;
	}

	public function getAnonymousAuth() {
		return null;
	}

	public function getMessageIDHost() {
		return "webnews.example.net";
	}

	protected function getSecretKey() {
		return $this->secretkey;
	}
}

?>
