<?php

require_once(dirname(__FILE__)."/classes/host.class.php");
require_once(dirname(__FILE__)."/classes/config.class.php");
require_once(dirname(__FILE__)."/classes/board.class.php");
require_once(dirname(__FILE__)."/classes/board/filecachednntp.class.php");
require_once(dirname(__FILE__)."/classes/board/memcachednntp.class.php");

require_once(dirname(__FILE__)."/classes/auth/jupis.class.php");
require_once(dirname(__FILE__)."/classes/template/smarty.class.php");

class JuPiConfig extends DefaultConfig {
	public function __construct() {
		parent::__construct();
		$this->addBoard(new Board(null, null, "Junge Piraten", ""));

		$this->addBoard(new FileCachedNNTPBoard(4, null, "Ankündigungen", $this->getNNTP_UCPLinks("announce", null) . "Moderiertes Forum für Ankündigungen.",
				false, true, true, $this->getNNTPHost(), $this->getNNTPGroup("announce")));
		$this->addBoard(new MemCachedNNTPBoard(2, null, "Allgemeines", $this->getNNTP_UCPLinks("misc", null) . "Globale Themen der Jungen Piraten",
				false, true, false, $this->getNNTPHost(), $this->getNNTPGroup("misc")));

		$this->addBoard(new Board(400, null, "Gliederungen", "Unterforen der Gebietsgruppen"));
		$this->addLVStruktur(500, 400);
		$this->addCrewStruktur(600, 400);

		$this->addTalkStruktur(700, null);
		
		$this->addBoard(new MemCachedNNTPBoard(666, null, "Test", $this->getNNTP_UCPLinks("test", "test") . "Testforum. Spamgefahr!",
				false, true, false, $this->getNNTPHost(), $this->getNNTPGroup("test")));
	}

	private function getNNTP_UCPLinks($name, $mlname) {
		$links = '';
		if ($name != null) {
			$links .= '[<a href="nntp://'.$this->getNNTPHost().'/'.$this->getNNTPGroup($name).'" class="nntplink">NNTP</a>] ';
		}
		if ($mlname != null) {
			$links .= '[<a href="https://lists.junge-piraten.de/listinfo/' . $mlname . '" class="mllink">ML</a>] ';
		}
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
	private function addLVStruktur($id, $parentid) {
		// Folge hier komplett den "alten" ids
		$this->addBoard(new Board(13, $parentid, "Landesverbände", "Unterforen der Landesverbände"));
		$this->addLVBoard(14, 13, "be", "Berlin",
						$this->getWikiLink("BE:Hauptseite"), "berlin");
		$this->addLVBoard(25, 13, "nw", "Nordrhein-Westfalen",
						$this->getWikiLink("NRW:Hauptseite"), "nrw");
		$this->addLVBoard(26, 13, "he", "Hessen",
						$this->getWikiLink("HE:Hauptseite"), null);
		$this->addLVBoard(28, 13, "bw", "Baden-Württemberg",
						$this->getWikiLink("BW:Hauptseite"), "bw");
		$this->addLVBoard(32, 13, "ni", "Niedersachsen",
						$this->getWikiLink("NDS:Hauptseite"), null);
		$this->addLVBoard(35, 13, "by", "Bayern",
						$this->getWikiLink("BY:Hauptseite"), "by");
		$this->addLVBoard(45, 13, "bb", "Brandenburg",
						$this->getWikiLink("BB:Hauptseite"), null);
		$this->addLVBoard(47, 13, "hb", "Bremen",
						$this->getWikiLink("HB:Hauptseite"), null);
		$this->addLVBoard(49, 13, "hh", "Hamburg",
						$this->getWikiLink("HH:Hauptseite"), "hamburg");
		$this->addLVBoard(51, 13, "mv", "Mecklenburg-Vorpommern",
						$this->getWikiLink("MV:Hauptseite"), null);
		$this->addLVBoard(53, 13, "rp", "Rheinland-Pfalz",
						$this->getWikiLink("RLP:Hauptseite"), "rlp");
		$this->addLVBoard(55, 13, "sl", "Saarland",
						$this->getWikiLink("SL:Hauptseite"), null);
		$this->addLVBoard(57, 13, "sn", "Sachsen",
						$this->getWikiLink("SN:Hauptseite"), null);
		$this->addLVBoard(59, 13, "st", "Sachsen Anhalt",
						$this->getWikiLink("ST:Hauptseite"), null);
		$this->addLVBoard(61, 13, "sh", "Schleswig-Holstein",
						$this->getWikiLink("SH:Hauptseite"), "sh");
		$this->addLVBoard(63, 13, "th", "Thüringen",
						$this->getWikiLink("TH:Hauptseite"), null);
	}

	private function addLVBoard($id, $parentid, $kuerzel, $name, $wikilink, $mlname) {
		$this->addBoard(new MemCachedNNTPBoard($id, $parentid, $name, $this->getNNTP_UCPLinks("gliederung.lv.{$kuerzel}", $mlname) . "[<a href=\"{$wikilink}\">Wiki: {$name}</a>]", false, true, false, $this->getNNTPHost(), $this->getNNTPGroup("gliederung.lv.{$kuerzel}")));
	}

	private function addCrewStruktur($id, $parentid) {
		$this->addBoard(new Board($id, $parentid, "Crews", "Unterforen der Crews"));
		$this->addCrewBoard($id+1, $id, "freiburg", "Freiburg",	$this->getWikiLink("BW:Freiburg"), "crew-freiburg");
		$this->addCrewBoard($id+2, $id, "quadrat", "Mannheim",	$this->getWikiLink("BW:Crew_JuPis%B2"), null);
	}

	private function addCrewBoard($id, $parentid, $kuerzel, $name, $wikilink, $mlname) {
		$this->addBoard(new MemCachedNNTPBoard($id, $parentid, $name, $this->getNNTP_UCPLinks("gliederung.crew.{$kuerzel}", $mlname) . "[<a href=\"{$wikilink}\">Wiki: {$name}</a>]", false, true, false, $this->getNNTPHost(), $this->getNNTPGroup("gliederung.crew.{$kuerzel}")));
	}

	private function addTalkStruktur($id, $parentid) {
		$this->addBoard(new Board($id, $parentid, "Diskussion", "Allgemeine Unterhaltungen zu Politik & Co."));
		$this->addTalkBoard($id+1, $id, "bildung",	"Bildungspolitik",	"");
		$this->addTalkBoard($id+2, $id, "umwelt",	"Umweltpolitik",	"");
		$this->addTalkBoard($id+3, $id, "kekse",	"Kekspolitik",		"Gegen das Keks-Embargo!");
		$this->addTalkBoard($id+4, $id, "misc",		"Sonstiges",		"");
	}

	private function addTalkBoard($id, $parentid, $kuerzel, $name, $desc, $mlname) {
		$this->addBoard(new MemCachedNNTPBoard($id, $parentid, $name, $this->getNNTP_UCPLinks("talk.{$kuerzel}", $mlname) . $desc, false, true, false, $this->getNNTPHost(), $this->getNNTPGroup("talk.{$kuerzel}")));
	}


	public function getAddressText($address, $charset) {
		$mailto = iconv($address->getCharset(), $charset, $address->getAddress());
		list($name, $host) = explode("@", $mailto);
		if ($host == "community.junge-piraten.de") {
			return ucfirst($name);
		}
		return parent::getAddressText($address, $charset);
	}
	public function getAddressLink($address, $charset) {
		$mailto = iconv($address->getCharset(), $charset, $address->getAddress());
		list($name, $host) = explode("@", $mailto);
		if ($host == "community.junge-piraten.de") {
			return $this->getWikiLink("Benutzer:" . ucfirst($name));
		}
		return parent::getAddressLink($address, $charset);
	}
	public function getAddressImage($address, $charset) {
		$mailto = iconv($address->getCharset(), $charset, $address->getAddress());
		list($name, $host) = explode("@", $mailto);
		if ($host == "community.junge-piraten.de") {
			return "jupisavatar.php?name=" . urlencode(ucfirst($name));
		}
		return parent::getAddressImage($address, $charset);
	}

	public function getTemplate($auth) {
		return new NNTPBoardSmarty($this, $this->getCharset(), $auth);
	}

	public function getAuth($user, $pass) {
		return JuPisAuth::authenticate($user, $pass);
	}

	public function getAnonymousAuth() {
		return new JuPisAnonAuth();
	}

	public function getMessageIDHost() {
		return "webnntp.junge-piraten.de";
	}

	protected function getSecretKey() {
		return "f1YkN08noJCvnQS9QUnz6dQhOjmjlX7k1pLKDOpbJW6ZLvHm";
	}
}

class TestConfig extends DefaultConfig {
	public function __construct() {
		parent::__construct();
		$this->addBoard(new Board(null, null, "Testboards", ""));

		$host = new Host("prauscher.homeip.net");

		$this->addBoard(new Board(900, null, "Boards", "Unterforen"));
		$this->addBoard(new FileCachedNNTPBoard(998, 900, "eins", "A",
				false, true, true, $host, "prauscher.test"));
		$this->addBoard(new MemCachedNNTPBoard(999, 900, "zwei", "B",
				false, true, false, $host, "prauscher.testing"));
	}
	
	public function getTemplate($auth) {
		return new NNTPBoardSmarty($this, $this->getCharset(), $auth);
	}

	public function getAuth($user, $pass) {
		return JuPisAuth::authenticate($user, $pass);
	}

	public function getAnonymousAuth() {
		return new JuPisAnonAuth();
	}

	public function getAddressText($address, $charset) {
		$mailto = iconv($address->getCharset(), $charset, $address->getAddress());
		list($name, $host) = explode("@", $mailto);
		if ($host == "auth.invalid") {
			return ucfirst($name);
		}
		return parent::getAddressText($address, $charset);
	}

	public function getMessageIDHost() {
		return "webnntp.prauscher.homelinux.net";
	}
	
	protected function getSecretKey() {
		return "f1YkN08noJCvnQS9QUnz6dQhOjmjlX7k1pLKDOpbJW6ZLvHm";
	}
}

$config = new TestConfig;

?>
