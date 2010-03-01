<?php

require_once(dirname(__FILE__)."/classes/config.class.php");
require_once(dirname(__FILE__)."/classes/board.class.php");

$config = new Config;
//$config->setHost("news.piratenpartei.de", "jupis_flint", "higRLd3zJ1hhhCo8");

/**
 * Boards
 */
$host = new Host("news.nerdnacht.de", 119);
//new Board("Techtalk", "techtalk halt", "pirates.de.orga.ag.it.techtalk");
//new Board("Struktur", "blabla", "pirates.de.etc.struktur");
$rootboard = $config->getBoard();
$rootboard->setName("NNTPBoard");

$nerdnachtde = new Board(10, "Nerdnacht DE", "Zum testen halt ;)", new Group($host, "nerdnacht.de"));
$rootboard->addSubBoard($nerdnachtde);

$testboard = new Board(20, "Testboard", "Anderes Board", new Group($host, "nerdnacht.test"));
$nerdnachtde->addSubBoard($testboard);

?>
