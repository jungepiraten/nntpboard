<?php

require_once(dirname(__FILE__)."/classes/config.class.php");
require_once(dirname(__FILE__)."/classes/board.class.php");

$config = new Config;
//$config->setHost("news.piratenpartei.de", "jupis_flint", "higRLd3zJ1hhhCo8");
$config->setHost(new Host("news.nerdnacht.de", 119));

/* Boards */
//new Board("Techtalk", "techtalk halt", "pirates.de.orga.ag.it.techtalk");
//new Board("Struktur", "blabla", "pirates.de.etc.struktur");
$config->addBoard($testboard = new Board(10, "Testboard", "Zum testen halt ;)", "nerdnacht.de"));

?>
