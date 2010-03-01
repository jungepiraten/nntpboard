<?php

require_once(dirname(__FILE__)."/classes/config.class.php");
require_once(dirname(__FILE__)."/classes/board.class.php");
require_once(dirname(__FILE__)."/classes/datadir.class.php");

$config = new Config;
$config->setDatadir(new Datadir(dirname(__PATH__)."/groups", "/~prauscher/nntpboard/groups"));
//$config->setHost("news.piratenpartei.de", "jupis_flint", "higRLd3zJ1hhhCo8");

/**
 * Boards
 */
//$host = new Host("news.nerdnacht.de", 119);
$host = new Host("news.piratenpartei.de");

$rootboard = $config->getBoard();
$rootboard->setName("NNTPBoard");

$techtalk = new Board(3, "Techtalk", "tech-bla-bla", new Group($host, "pirates.de.orga.ag.it.techtalk", "jupis_flint", "higRLd3zJ1hhhCo8"));
$rootboard->addSubBoard($techtalk);

$struktur = new Board(4, "Struktur", "hihi", new Group($host, "pirates.de.etc.struktur", "jupis_flint", "higRLd3zJ1hhhCo8"));
$techtalk->addSubBoard($struktur);

/*
$nerdnachtde = new Board(10, "Nerdnacht DE", "Zum testen halt ;)", new Group($host, "nerdnacht.de"));
$rootboard->addSubBoard($nerdnachtde);

$testboard = new Board(20, "Testboard", "Anderes Board", new Group($host, "nerdnacht.test"));
$nerdnachtde->addSubBoard($testboard);
*/

?>
