<?php

require_once(dirname(__FILE__)."/classes/host.class.php");
require_once(dirname(__FILE__)."/classes/config.class.php");
require_once(dirname(__FILE__)."/classes/board.class.php");
require_once(dirname(__FILE__)."/classes/board/filecachednntp.class.php");
require_once(dirname(__FILE__)."/classes/board/memcachednntp.class.php");
require_once(dirname(__FILE__)."/classes/board/sqlcachednntp.class.php");

require_once(dirname(__FILE__)."/classes/auth/jupis.class.php");
require_once(dirname(__FILE__)."/classes/template/smarty.class.php");

class PrauscherConfig extends DefaultConfig {
	public function __construct() {
		$this->addBoard(new Board(null, null, "NNTPBoard", "Junge Piraten Forum ..."));

		$nntphost = new Host("news.nerdnacht.de");
		$nntpprefix = "nerdnacht";

		$this->addBoard(new Board(100, null, "Nerdnacht", ""));
		$this->addBoard(new FileCachedNNTPBoard(110, 100, "Deutsch", "Zum testen halt ;)", false, true, false, $nntphost, $nntpprefix.".de"));
		$this->addBoard(new FileCachedNNTPBoard(120, 100, "Testboard", "Anderes Board", false, true, false, $nntphost, $nntpprefix.".test"));

		$this->addBoard(new Board(200, null, "Prauscher", ""));
		$this->addBoard(new FileCachedNNTPBoard(210, 200, "Testbasis", false, true, true, "Prauschers Testbasis. MODERIERT!", $nntphost, "prauscher.test"));

		$this->addBoard(new FileCachedNNTPBoard(300, null, "de.comp.misc", false, true, true, "VIEL INHALT!", new Host("news.arcor-ip.de"), "de.comp.misc"));

		$this->addBoard(new FileCachedNNTPBoard(400, null, "de.comp.os.unix.linux.misc", false, true, true, "VIEL INHALT!", new Host("news.arcor-ip.de"), "de.comp.os.unix.linux.misc"));
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

class JuPiConfig extends DefaultConfig {
	public function __construct() {
		$this->addBoard(new Board(null, null, "Junge Piraten", "Junge Piraten Forum"));

		$this->addBoard(new FileCachedNNTPBoard(4, null, "Ankündigungen", "Moderiertes Forum für Ankündigungen.",
				false, true, true, $this->getNNTPHost(), $this->getNNTPGroup("announce")));
		$this->addBoard(new MemCachedNNTPBoard(2, null, "Allgemeines", "Globale Themen der Jungen Piraten",
				false, true, false, $this->getNNTPHost(), $this->getNNTPGroup("misc")));

		$this->addBoard(new Board(100, null, "Struktur", "Unterforen der Arbeitsgruppen"));
		$this->addAGStruktur(200, 100);
		$this->addPGStruktur(300, 100);

		$this->addBoard(new Board(400, null, "Gliederungen", "Unterforen der Gebietsgruppen"));
		$this->addLVStruktur(500, 400);
		$this->addCrewStruktur(600, 400);

		$this->addTalkStruktur(700, null);
		
		$this->addBoard(new MemCachedNNTPBoard(666, null, "Test", "Testforum. Spamgefahr!",
				false, true, false, $this->getNNTPHost(), $this->getNNTPGroup("test")));
	}

	private function getWikiLink($topic) {
		return "http://wiki.junge-piraten.de/wiki/{$topic}";
	}

	private function getNNTPHost() {
		return new Host("news.junge-piraten.de");
	}

	private function getNNTPGroup($name) {
		return "pirates.youth.de.{$name}";
	}

	private function addAGStruktur($id, $parentid) {
		$this->addBoard(new Board(5, $parentid, "Arbeitsgruppen", "Unterforen der Arbeitsgruppen"));
		$this->addAGBoard( 7, 5, "it",		"AG IT",
								$this->getWikiLink("AG_IT"));
		$this->addAGBoard(12, 5, "oe",		"AG Öffentlichkeitsarbeit",
								$this->getWikiLink("AG_Öffentlichkeitsarbeit"));
		$this->addAGBoard( 9, 5, "programm",		"AG Programm",
								$this->getWikiLink("AG_Programm"));
		$this->addAGBoard(66, 5, "struktur",		"AG Struktur",
								$this->getWikiLink("AG_Struktur"));
		$this->addAGBoard(72, 5, "satzung",		"AG Satzung",
								$this->getWikiLink("AG_Satzung"));
		$this->addAGBoard(73, 5, "fs",		"AG Freie Software",
								$this->getWikiLink("AG_Freie_Software"));
		$this->addAGBoard(38, 5, "international",	"AG International",
								$this->getWikiLink("AG_International"));
	}

	private function addAGBoard($id, $parentid, $kuerzel, $name, $wikilink) {
		$this->addBoard(new MemCachedNNTPBoard($id, $parentid, $name, "<a href=\"{$wikilink}\">{$name}</a>", false, true, false, $this->getNNTPHost(), $this->getNNTPGroup("struktur.ag.{$kuerzel}")));
	}

	private function addPGStruktur($id, $parentid) {
		$this->addBoard(new Board($id, $parentid, "Projektgruppen", "Unterforen der Projektgruppen"));
		$this->addPGBoard($id+1, $id, "killermaerchen",	"PG Killermärchen",
								$this->getWikiLink("PG_Killermärchen"));
		$this->addPGBoard(   74, $id, "hdddz",		"PG HDDDZ",
								$this->getWikiLink("PG_HDDDZ"));
		$this->addPGBoard($id+3, $id, "neue-homepage",	"PG Neue Homepage",
								$this->getWikiLink("PG_Neue_Homepage"));
		$this->addPGBoard($id+4, $id, "jupi-camp",	"PG JuPi-Camp",
								$this->getWikiLink("PG_JuPi-Camp"));
	}

	private function addPGBoard($id, $parentid, $kuerzel, $name, $wikilink) {
		$this->addBoard(new MemCachedNNTPBoard($id, $parentid, $name, "<a href=\"{$wikilink}\">{$name}</a>", false, true, false, $this->getNNTPHost(), $this->getNNTPGroup("struktur.pg.{$kuerzel}")));
	}

	private function addLVStruktur($id, $parentid) {
		// Folge hier komplett den "alten" ids
		$this->addBoard(new Board(13, $parentid, "Landesverbände", "Unterforen der Landesverbände"));
		$this->addLVBoard(14, 13, "be", "Berlin",			$this->getWikiLink("BE:Hauptseite"));
		$this->addLVBoard(25, 13, "nw", "Nordrhein-Westfalen",		$this->getWikiLink("NRW:Hauptseite"));
		$this->addLVBoard(26, 13, "he", "Hessen",			$this->getWikiLink("HE:Hauptseite"));
		$this->addLVBoard(28, 13, "bw", "Baden-Württemberg",		$this->getWikiLink("BW:Hauptseite"));
		$this->addLVBoard(32, 13, "ni", "Niedersachsen",		$this->getWikiLink("NDS:Hauptseite"));
		$this->addLVBoard(35, 13, "by", "Bayern",			$this->getWikiLink("BY:Hauptseite"));
		$this->addLVBoard(45, 13, "bb", "Brandenburg",			$this->getWikiLink("BB:Hauptseite"));
		$this->addLVBoard(47, 13, "hb", "Bremen",			$this->getWikiLink("HB:Hauptseite"));
		$this->addLVBoard(49, 13, "hh", "Hamburg",			$this->getWikiLink("HH:Hauptseite"));
		$this->addLVBoard(51, 13, "mv", "Mecklenburg-Vorpommern",	$this->getWikiLink("MV:Hauptseite"));
		$this->addLVBoard(53, 13, "rp", "Rheinland-Pfalz",		$this->getWikiLink("RLP:Hauptseite"));
		$this->addLVBoard(55, 13, "sl", "Saarland",			$this->getWikiLink("SL:Hauptseite"));
		$this->addLVBoard(57, 13, "sn", "Sachsen",			$this->getWikiLink("SN:Hauptseite"));
		$this->addLVBoard(59, 13, "st", "Sachsen Anhalt",		$this->getWikiLink("ST:Hauptseite"));
		$this->addLVBoard(61, 13, "sh", "Schleswig-Holstein",		$this->getWikiLink("SH:Hauptseite"));
		$this->addLVBoard(63, 13, "th", "Thüringen",			$this->getWikiLink("TH:Hauptseite"));
	}

	private function addLVBoard($id, $parentid, $kuerzel, $name, $wikilink) {
		$this->addBoard(new MemCachedNNTPBoard($id, $parentid, $name, "<a href=\"{$wikilink}\">{$name}</a>", false, true, false, $this->getNNTPHost(), $this->getNNTPGroup("gliederung.lv.{$kuerzel}")));
	}

	private function addCrewStruktur($id, $parentid) {
		$this->addBoard(new Board($id, $parentid, "Crews", "Unterforen der Crews"));
		$this->addCrewBoard($id+1, $id, "freiburg", "Freiburg",	$this->getWikiLink("BW:Freiburg"));
		$this->addCrewBoard($id+2, $id, "quadrat", "Mannheim",	$this->getWikiLink("BW:Crew_JuPis%B2"));
	}

	private function addCrewBoard($id, $parentid, $kuerzel, $name, $wikilink) {
		$this->addBoard(new MemCachedNNTPBoard($id, $parentid, $name, "<a href=\"{$wikilink}\">{$name}</a>", false, true, false, $this->getNNTPHost(), $this->getNNTPGroup("gliederung.crew.{$kuerzel}")));
	}

	private function addTalkStruktur($id, $parentid) {
		$this->addBoard(new Board($id, $parentid, "Unterhaltung", "Allgemeine Unterhaltungen zu Politik & Co."));
		$this->addTalkBoard($id+1, $id, "bildung",	"Bildungspolitik",	"");
		$this->addTalkBoard($id+2, $id, "umwelt",	"Umweltpolitik",	"");
		$this->addTalkBoard($id+3, $id, "kekse",	"Kekspolitik",		"Gegen das Keks-Embargo!");
		$this->addTalkBoard($id+4, $id, "misc",		"Sonstiges",		"");
	}

	private function addTalkBoard($id, $parentid, $kuerzel, $name, $desc) {
		$this->addBoard(new MemCachedNNTPBoard($id, $parentid, $name, $desc, false, true, false, $this->getNNTPHost(), $this->getNNTPGroup("talk.{$kuerzel}")));
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
		return "webnntp.junge-piraten.de";
	}
}

$config = new PrauscherConfig;

?>
