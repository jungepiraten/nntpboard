<?php

require_once(dirname(__FILE__)."/classes/host.class.php");
require_once(dirname(__FILE__)."/classes/config.class.php");
require_once(dirname(__FILE__)."/classes/board.class.php");
require_once(dirname(__FILE__)."/classes/board/cachednntp.class.php");

require_once(dirname(__FILE__)."/classes/auth/jupis.class.php");
require_once(dirname(__FILE__)."/classes/template/smarty.class.php");

class Config extends DefaultConfig {
	public function __construct() {
		$this->addBoard(new Board(null, null, "NNTPBoard", "Junge Piraten Forum ..."));

		$host = new Host("news.nerdnacht.de");

		$this->addBoard(new Board(100, null, "Nerdnacht", ""));
		$this->addBoard(new CachedNNTPBoard(110, 100, "Deutsch", "Zum testen halt ;)", $host, "nerdnacht.de"));
		$this->addBoard(new CachedNNTPBoard(120, 100, "Testboard", "Anderes Board", $host, "nerdnacht.test"));

		$this->addBoard(new Board(200, null, "Prauscher", ""));
		$this->addBoard(new CachedNNTPBoard(210, 200, "Testbasis", "Prauschers Testbasis. MODERIERT!", $host, "prauscher.test"));
	}

	public function getTemplate($auth) {
		return new NNTPBoardSmarty($this->getCharset(), $auth);
	}

	public function getAuth($user, $pass) {
		return JuPisAuth::authenticate($user, $pass);
	}

	public function getAnonymousAuth() {
		return JuPisAuth::getAnonymousAuth();
	}

	public function getMessageIDHost() {
		return "testwebserver.prauscher.homelinux.net";
	}
}

$config = new Config;

?>
