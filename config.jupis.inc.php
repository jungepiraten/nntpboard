<?php

require_once(dirname(__FILE__)."/classes/host.class.php");
require_once(dirname(__FILE__)."/classes/memcachehost.class.php");
require_once(dirname(__FILE__)."/classes/config.class.php");
require_once(dirname(__FILE__)."/classes/board.class.php");
require_once(dirname(__FILE__)."/classes/board/memcachednntp.class.php");

require_once(dirname(__FILE__)."/classes/auth/jupis.class.php");
require_once(dirname(__FILE__)."/classes/template/smarty.class.php");

class JuPiConfig extends DefaultConfig {
	private $secretkey;
	
	public function __construct($secretkey) {
		parent::__construct();
		$this->addBoard(new Board(null, null, "Junge Piraten", ""));

		$this->addGenericBoard(4, null, "announce", "announce", null, "Ankündigungen", "Allgemeine Ankündigungen");
		$this->addGenericBoard(2, null, "misc", "aktive", null, "Allgemeines", "Globale Themen der Jungen Piraten");

		$this->addOrgaStruktur(300, null);

		$this->addBoard(new Board(400, null, "Gliederungen", "Unterforen der Gebietsgruppen"));
		$this->addLVStruktur(500, 400);
		$this->addCrewStruktur(600, 400);

		$this->addTalkStruktur(700, null);
		$this->addEventStruktur(800, null);
		
		$this->addGenericBoard(666, null, "test", "test", null, "Test", "Testforum. Spamgefahr!");

		$this->secretkey = $secretkey;
	}

	private function getNNTP_UCPLinks($name = null, $mlname = null, $wiki = null) {
		$links = '';
		if ($name != null) {
			$links .= '[<a href="nntp://'.$this->getNNTPHost().'/'.$this->getNNTPGroup($name).'" class="nntplink">NNTP</a>] ';
		}
		if ($mlname != null) {
			$links .= '[<a href="http://lists.junge-piraten.de/listinfo/' . $mlname . '" class="mllink">ML</a>] ';
		}
		if ($wiki != null) {
			$links .= '[<a href="http://wiki.junge-piraten.de/wiki/' . $wiki . '" class="wiki">WIKI</a>] ';
		}
		return $links;
	}

	private function getNNTPHost() {
		return new Host("news.junge-piraten.de");
	}

	private function getMemcacheHost($boardid) {
		return new MemCacheHost("storage", 11211, "nntpboard" . $boardid);
	}

	private function getNNTPGroup($name) {
		return "pirates.youth.de.{$name}";
	}

	private function addGenericBoard($id, $parentid, $group, $mlname, $wiki, $name, $desc) {
		$this->addBoard(new MemCachedNNTPBoard($id, $parentid, $name, $this->getNNTP_UCPLinks($group, $mlname, $wiki) . $desc, false, true, false, $this->getMemcacheHost($id), $this->getNNTPHost(), $this->getNNTPGroup($group)));
	}

	private function addOrgaStruktur($id, $parentid) {
		$this->addBoard(new Board($id, $parentid, "Organisation", ""));
		$this->addOrgaBoard($id+1, $id, "it",	"IT",				"Planung der Infrastruktur", "IT:Hauptseite", "ag-it");
		$this->addOrgaBoard($id+2, $id, "oe",	"Öffentlichkeitsarbeit",	"Öffentlichkeitsarbeit", "AG_Oe", "ag-oe");
	}
	private function addOrgaBoard($id, $parentid, $kuerzel, $name, $desc, $wiki, $mlname) {
		$this->addGenericBoard($id, $parentid, "orga.{$kuerzel}", $mlname, $wiki, $name, $desc);
	}

	private function addLVStruktur($id, $parentid) {
		// Folge hier komplett den "alten" ids
		$this->addBoard(new Board(13, $parentid, "Landesverbände", "Unterforen der Landesverbände"));
		$this->addLVBoard(14, 13, "be", "Berlin", "BE:Hauptseite", "berlin");
		$this->addLVBoard(25, 13, "nrw", "Nordrhein-Westfalen", "NRW:Hauptseite", "nrw");
		$this->addLVBoard(26, 13, "he", "Hessen", "HE:Hauptseite", "he");
		$this->addLVBoard(28, 13, "bw", "Baden-Württemberg", "BW:Hauptseite", "bw");
		$this->addLVBoard(32, 13, "nds", "Niedersachsen", "NDS:Hauptseite", "ni");
		$this->addLVBoard(35, 13, "by", "Bayern", "BY:Hauptseite", "by");
		$this->addLVBoard(100,35, "by.mfr", "Mittelfranken", "BY:Mittelfranken", "by-mfr");
		$this->addLVBoard(45, 13, "bb", "Brandenburg", "BB:Hauptseite", "bb");
		$this->addLVBoard(47, 13, "hb", "Bremen", "HB:Hauptseite", "hb");
		$this->addLVBoard(49, 13, "hh", "Hamburg", "HH:Hauptseite", "hamburg");
		$this->addLVBoard(51, 13, "mv", "Mecklenburg-Vorpommern", "MV:Hauptseite", "mv");
		$this->addLVBoard(53, 13, "rlp", "Rheinland-Pfalz", "RLP:Hauptseite", "rlp");
		$this->addLVBoard(55, 13, "sl", "Saarland", "SL:Hauptseite", "sl");
		$this->addLVBoard(57, 13, "sn", "Sachsen", "SN:Hauptseite", "sn");
		$this->addLVBoard(59, 13, "lsa", "Sachsen Anhalt", "LSA:Hauptseite", "st");
		$this->addLVBoard(61, 13, "sh", "Schleswig-Holstein", "SH:Hauptseite", "sh");
		$this->addLVBoard(63, 13, "th", "Thüringen", "TH:Hauptseite", "th");
	}
	private function addLVBoard($id, $parentid, $kuerzel, $name, $wiki, $mlname) {
		$this->addGenericBoard($id, $parentid, "gliederung.lv.{$kuerzel}", $mlname, $wiki, $name, $desc);
	}

	private function addCrewStruktur($id, $parentid) {
		$this->addBoard(new Board($id, $parentid, "Crews", "Unterforen der Crews"));
		$this->addCrewBoard($id+1, $id, "freiburg", "Freiburg",	"BW:Freiburg", "crew-freiburg");
		$this->addCrewBoard($id+2, $id, "quadrat", "Mannheim",	"BW:Crew_JuPis%B2", null);
	}
	private function addCrewBoard($id, $parentid, $kuerzel, $name, $wiki, $mlname) {
		$this->addGenericBoard($id, $parentid, "gliederung.crew.{$kuerzel}", $mlname, $wiki, $name, $desc);
	}

	private function addTalkStruktur($id, $parentid) {
		$this->addBoard(new Board($id, $parentid, "Diskussion", "Allgemeine Unterhaltungen zu Politik & Co."));
		$this->addTalkBoard($id+1, $id, "bildung",	"Bildungspolitik",	"", null, "talk-bildung");
		$this->addTalkBoard($id+2, $id, "umwelt",	"Umweltpolitik",	"", null, "talk-umwelt");
		$this->addTalkBoard($id+3, $id, "kekse",	"Kekspolitik",		"Gegen das Keks-Embargo!", null, "talk-kekse");
		$this->addTalkBoard($id+4, $id, "misc",		"Sonstiges",		"Was sonst nicht relevant waere", null, "talk-sonstiges");
	}
	private function addTalkBoard($id, $parentid, $kuerzel, $name, $desc, $wiki, $mlname) {
		$this->addGenericBoard($id, $parentid, "talk.{$kuerzel}", $mlname, $wiki, $name, $desc);
	}

	private function addEventStruktur($id, $parentid) {
		$this->addBoard(new Board($id, $parentid, "Events", ""));
		$this->addEventBoard($id+1, $id, "camp",	"JuPi-Camp",	"Planungsbereich fuer das JuPi-Camp", "JuPi-Camp_2011", "pg-jupi-camp");
	}
	private function addEventBoard($id, $parentid, $kuerzel, $name, $desc, $wiki, $mlname) {
		$this->addGenericBoard($id, $parentid, "event.{$kuerzel}", $mlname, $wiki, $name, $desc);
	}


	public function getAddressText($address, $charset) {
		$mailto = iconv($address->getCharset(), $charset, $address->getAddress());
		list($name, $host) = explode("@", $mailto);
		if ($host == "community.junge-piraten.de") {
			return ucfirst($name);
		}
		if ($host == "junge-piraten.de") {
			return ucwords(str_replace("."," ",$name));
		}
		return $name . "@..."; 
	}
	public function getAddressLink($address, $charset) {
		$mailto = iconv($address->getCharset(), $charset, $address->getAddress());
		list($name, $host) = explode("@", $mailto);
		if ($host == "community.junge-piraten.de") {
			return "http://wiki.junge-piraten.de/wiki/Benutzer:" . ucfirst($name);
		}
		return "";
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
		return $this->secretkey;
	}
}

?>
