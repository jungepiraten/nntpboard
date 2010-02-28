<?php

require_once(dirname(__FILE__)."/classes/config.class.php");
require_once(dirname(__FILE__)."/classes/board.class.php");

$config = new Config;
$config->setName("NNTPBoard");
//$config->setHost("news.piratenpartei.de", "jupis_flint", "higRLd3zJ1hhhCo8");
$host = new Host("news.nerdnacht.de", 119);

/* Boards */
//new Board("Techtalk", "techtalk halt", "pirates.de.orga.ag.it.techtalk");
//new Board("Struktur", "blabla", "pirates.de.etc.struktur");
$config->addBoard($testboard1 = new Board(10, "Nerdnacht DE", "Zum testen halt ;)", new Group($host, "nerdnacht.de")));
$config->addBoard($testboard2 = new Board(20, "Testboard", "Anderes Board", new Group($host, "nerdnacht.test"), $testboard1));

?>
