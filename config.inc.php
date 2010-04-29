<?php

require_once(dirname(__FILE__)."/classes/auth/jupis.class.php");
require_once(dirname(__FILE__)."/classes/config.class.php");
require_once(dirname(__FILE__)."/classes/board.class.php");
require_once(dirname(__FILE__)."/classes/datadir.class.php");

class Config extends DefaultConfig {
	private $boards;

	public function __construct() {
		$this->boards[null] = new Board(null, "NNTPBoard", "Description", null);

		$board = new Board(1, "Nerdnacht DE", "Zum testen halt ;)",
		    new Group(new Host("news.nerdnacht.de"), "nerdnacht.de", Group::READMODE_OPEN, Group::POSTMODE_AUTH));
		$this->boards[null]->addSubBoard($board);
		$this->boards[$board->getBoardID()] = $board;

		$board = new Board(2, "Testboard", "Anderes Board",
		    new Group(new Host("news.nerdnacht.de"), "nerdnacht.test", Group::READMODE_OPEN, Group::POSTMODE_AUTH));
		$this->boards[1]->addSubBoard($board);
		$this->boards[$board->getBoardID()] = $board;

		$board = new Board(3, "Testbasis prauscher", "Prauschers Testbasis. MODERIERT!",
		    new Group(new Host("news.nerdnacht.de"), "prauscher.test", Group::READMODE_OPEN, Group::POSTMODE_MODERATED_AUTH));
		$this->boards[null]->addSubBoard($board);
		$this->boards[$board->getBoardID()] = $board;
	}

	public function getBoard($id = null) {
		return $this->boards[$id];
	}

	public function getBoards() {
		return $this->boards;
	}

	public function getAuth($user, $pass) {
		return JuPisAuth::authenticate($user, $pass);
	}

	public function getDataDir() {
		return new Datadir(dirname(__FILE__)."/data", "/~prauscher/nntpboard/data");
	}

	public function getAnonymousAuth() {
		return JuPisAuth::getAnonymousAuth();
	}

	public function getMessageIDHost() {
		return "testwebserver.prauscher.homelinux.net";
	}
}

$config = new Config;
//var_dump($config->getBoard(2)->getGroup()->getConnection());

if (false) {
	$host = new Host("news.piratenpartei.de");

	$boardid = 1;

	$orgaboard = new Board($boardid++, "Organisation", null, null);
	$rootboard->addSubBoard($orgaboard);

	$techtalkboard = new Board($boardid++, "Techtalk", "tech-bla-bla", new Group($host, "pirates.de.orga.ag.it.techtalk", "jupis_flint", "higRLd3zJ1hhhCo8"));
	$orgaboard->addSubBoard($techtalkboard);

	$etcboard = new Board($boardid++, "Sonstiges", null, null);
	$rootboard->addSubBoard($etcboard);

	$strukturboard = new Board($boardid++, "Struktur", "hihi", new Group($host, "pirates.de.etc.struktur", "jupis_flint", "higRLd3zJ1hhhCo8"));
	$etcboard->addSubBoard($strukturboard);
}

?>
