<?php

require_once(dirname(__FILE__)."/classes/config.class.php");
require_once(dirname(__FILE__)."/classes/board.class.php");
require_once(dirname(__FILE__)."/classes/datadir.class.php");

$config = new Config;
$config->setDatadir(new Datadir(dirname(__FILE__)."/groups", "/~prauscher/nntpboard/groups"));
//$config->setHost("news.piratenpartei.de", "jupis_flint", "higRLd3zJ1hhhCo8");

/**
 * Boards
 */

$rootboard = $config->getBoard();
$rootboard->setName("NNTPBoard");

if (true) {
	$host = new Host("news.piratenpartei.de");

	$orgaboard = new Board(100, "Organisation", null, null);
	$techtalkboard = new Board(101, "Techtalk", "tech-bla-bla", new Group($host, "pirates.de.orga.ag.it.techtalk", "jupis_flint", "higRLd3zJ1hhhCo8"));
	$orgaboard->addSubBoard($techtalkboard);

	$etcboard = new Board(200, "Sonstiges", null, null);
	$strukturboard = new Board(201, "Struktur", "hihi", new Group($host, "pirates.de.etc.struktur", "jupis_flint", "higRLd3zJ1hhhCo8"));
	$etcboard->addSubBoard($strukturboard);

	$rootboard->addSubBoard($orgaboard);
	$rootboard->addSubBoard($etcboard);
} else {
	$host = new Host("news.nerdnacht.de", 119);

	$nerdnachtde = new Board(10, "Nerdnacht DE", "Zum testen halt ;)", new Group($host, "nerdnacht.de"));
	$rootboard->addSubBoard($nerdnachtde);

	$testboard = new Board(20, "Testboard", "Anderes Board", new Group($host, "nerdnacht.test"));
	$nerdnachtde->addSubBoard($testboard);
}

?>
