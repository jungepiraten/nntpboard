<?php

require_once(dirname(__FILE__)."/classes/host.class.php");
require_once(dirname(__FILE__)."/classes/config.class.php");
require_once(dirname(__FILE__)."/classes/board.class.php");
require_once(dirname(__FILE__)."/classes/connection/cacheprovider/file.class.php");
require_once(dirname(__FILE__)."/classes/connection/cache.class.php");
require_once(dirname(__FILE__)."/classes/connection/nntp.class.php");

require_once(dirname(__FILE__)."/classes/auth/jupis.class.php");
require_once(dirname(__FILE__)."/classes/template/smarty.class.php");

class Config extends DefaultConfig {
	public function __construct() {
		$this->addBoard(null, new Board(null, "NNTPBoard", "Junge Piraten Forum ...", null));

		$host = new Host("news.nerdnacht.de");

		$this->addBoard(null, new Board(100, "Nerdnacht", ""));
		$this->addBoard(100, new Board(110, "Deutsch", "Zum testen halt ;)",
		                         new CacheConnection(
		                             new FileCacheProvider(dirname(__FILE__) . "/cache/nerdnacht.de/"),
		                             new NNTPConnection($host, "nerdnacht.de")
		                         )
		                     ) );
		$this->addBoard(100, new Board(120, "Testboard", "Anderes Board",
		                         new CacheConnection(
		                             new FileCacheProvider(dirname(__FILE__) . "/cache/nerdnacht.test/"),
		                             new NNTPConnection($host, "nerdnacht.test")
		                         )
		                     ) );

		$this->addBoard(null, new Board(200, "Prauscher", ""));
		$this->addBoard(200, new Board(210, "Testbasis", "Prauschers Testbasis. MODERIERT!",
		                         new CacheConnection(
		                             new FileCacheProvider(dirname(__FILE__) . "/cache/prauscher.test/"),
		                             new NNTPConnection($host, "prauscher.test")
		                         )
		                     ) );
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
