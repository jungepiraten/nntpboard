<?php

require_once(dirname(__FILE__)."/classes/config.class.php");
require_once(dirname(__FILE__)."/classes/board.class.php");
require_once(dirname(__FILE__)."/classes/datadir.class.php");

$config = new Config;
$config->setDatadir(new Datadir(dirname(__FILE__)."/data", "/~prauscher/nntpboard/data"));

/**
 * Boards
 */

$rootboard = $config->getBoard();
$rootboard->setName("NNTPBoard");

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
} else {
	$host = new Host("news.nerdnacht.de");

	$boardid = 1;

	$nerdnachtde = new Board($boardid++, "Nerdnacht DE", "Zum testen halt ;)", new Group($host, "nerdnacht.de"));
	$rootboard->addSubBoard($nerdnachtde);

	$testboard = new Board($boardid++, "Testboard", "Anderes Board", new Group($host, "nerdnacht.test"));
	$nerdnachtde->addSubBoard($testboard);

	$prauschertest = new Board($boardid++, "Testbasis prauscher", "Prauschers Testbasis", new Group($host, "prauscher.test"));
	$rootboard->addSubBoard($prauschertest);
}

?>
