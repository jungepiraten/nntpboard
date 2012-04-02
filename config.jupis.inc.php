<?php

// http://pear.php.net/package/Net_LDAP2
require_once("Net/LDAP2.php");

require_once(dirname(__FILE__)."/classes/host.class.php");
require_once(dirname(__FILE__)."/classes/memcachehost.class.php");
require_once(dirname(__FILE__)."/classes/config.class.php");
require_once(dirname(__FILE__)."/classes/board.class.php");
require_once(dirname(__FILE__)."/classes/board/memcachednntp.class.php");

require_once(dirname(__FILE__)."/classes/auth/jupis.class.php");
require_once(dirname(__FILE__)."/classes/template/smarty.class.php");

class JuPiConfig extends DefaultConfig {
	private $secretkey;
	private $memcachelink;
	private $ldappass;
	private $mailusers = array();
	
	public function __construct($secretkey, $ldappass) {
		parent::__construct();
		$this->addBoard(new Board(null, null, "Junge Piraten", ""));

		$this->addGenericBoard(4, null, "announce", "announce", null, "Ankündigungen", "Allgemeine Ankündigungen");
		$this->addGenericBoard(2, null, "misc", "aktive", null, "Allgemeines", "Globale Themen der Jungen Piraten");

		$this->addOrgaStruktur(300, null);

		$this->addRegionStruktur(500, null);

		$this->addTalkStruktur(700, null);
		$this->addEventStruktur(800, null);
		
		$this->addGenericBoard(666, null, "test", "test", null, "Test", "Testforum. Spamgefahr!");

		$this->addBoard(new Board(899, null, "Young Pirates International", ""));
		$this->addInternationalBoard(900, 899, "misc", "ypi", null, "Misc", "Miscellanganeous");

		$this->secretkey = $secretkey;
		$this->ldappass = $ldappass;
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
		return "pirates.youth.{$name}";
	}

	private function addGenericBoard($id, $parentid, $group, $mlname, $wiki, $name, $desc) {
		$this->addBoard(new MemCachedNNTPBoard($id, $parentid, $name, $this->getNNTP_UCPLinks("de." . $group, $mlname, $wiki) . $desc, false, true, false, $this->getMemcacheHost($id), $this->getNNTPHost(), $this->getNNTPGroup("de." . $group)));
	}
	private function addInternationalBoard($id, $parentid, $group, $mlname, $wiki, $name, $desc) {
		$this->addBoard(new MemCachedNNTPBoard($id, $parentid, $name, $this->getNNTP_UCPLinks("int." . $group, $mlname, $wiki) . $desc, false, true, false, $this->getMemcacheHost($id), $this->getNNTPHost(), $this->getNNTPGroup("int." . $group)));
	}

	private function addOrgaStruktur($id, $parentid) {
		$this->addBoard(new Board($id, $parentid, "Organisation", ""));
		$this->addOrgaBoard($id+1, $id, "it",	"IT",				"Planung der Infrastruktur", "IT:Hauptseite", "ag-it");
		$this->addOrgaBoard($id+2, $id, "oe",	"Öffentlichkeitsarbeit",	"Öffentlichkeitsarbeit", "AG_Oe", "ag-oe");
	}
	private function addOrgaBoard($id, $parentid, $kuerzel, $name, $desc, $wiki, $mlname) {
		$this->addGenericBoard($id, $parentid, "orga.{$kuerzel}", $mlname, $wiki, $name, $desc);
	}

	private function addRegionStruktur($id, $parentid) {
		// Folge hier komplett den "alten" ids
		$this->addBoard(new Board(13, $parentid, "Regionale Gliederungen", "Unterforen der Gebietsgruppen"));
		$this->addRegionBoard(14, 13, "be", "Berlin", "BE:Hauptseite", "be");
		$this->addRegionBoard(25, 13, "nrw", "Nordrhein-Westfalen", "NRW:Hauptseite", "nrw");
		$this->addRegionBoard(110,25, "nrw.do", "Dortmund", "NRW:Dortmund", "nrw-do");
		$this->addRegionBoard(26, 13, "he", "Hessen", "HE:Hauptseite", "he");
		$this->addRegionBoard(28, 13, "bw", "Baden-Württemberg", "BW:Hauptseite", "bw");
		$this->addRegionBoard(601,28, "bw.freiburg", "Freiburg", "BW:Freiburg", "crew-freiburg");
		$this->addRegionBoard(32, 13, "nds", "Niedersachsen", "NDS:Hauptseite", "nds");
		$this->addRegionBoard(35, 13, "by", "Bayern", "BY:Hauptseite", "by");
		$this->addRegionBoard(100,35, "by.mfr", "Mittelfranken", "BY:Mittelfranken", "by-mfr");
		$this->addRegionBoard(101,35, "by.muc", "München", "BY:Muenchen", "by-muc");
		$this->addRegionBoard(45, 13, "bb", "Brandenburg", "BB:Hauptseite", "bb");
		$this->addRegionBoard(47, 13, "hb", "Bremen", "HB:Hauptseite", "hb");
		$this->addRegionBoard(49, 13, "hh", "Hamburg", "HH:Hauptseite", "hamburg");
		$this->addRegionBoard(51, 13, "mv", "Mecklenburg-Vorpommern", "MV:Hauptseite", "mv");
		$this->addRegionBoard(53, 13, "rlp", "Rheinland-Pfalz", "RLP:Hauptseite", "rlp");
		$this->addRegionBoard(55, 13, "sl", "Saarland", "SL:Hauptseite", "sl");
		$this->addRegionBoard(57, 13, "sn", "Sachsen", "SN:Hauptseite", "sn");
		$this->addRegionBoard(59, 13, "lsa", "Sachsen Anhalt", "LSA:Hauptseite", "lsa");
		$this->addRegionBoard(61, 13, "sh", "Schleswig-Holstein", "SH:Hauptseite", "sh");
		$this->addRegionBoard(63, 13, "th", "Thüringen", "TH:Hauptseite", "th");
	}
	private function addRegionBoard($id, $parentid, $kuerzel, $name, $wiki, $mlname) {
		$this->addGenericBoard($id, $parentid, "region.{$kuerzel}", $mlname, $wiki, $name, $desc);
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
		$this->addEventBoard($id+1, $id, "camp",	"JuPi-Camp",	"Planungsbereich fuer das JuPi-Camp", "JuPi-Camp", "pg-jupi-camp");
		$this->addEventBoard($id+2, $id, "you-messe",	"YOU 2012",	"Vorbereitung zur YOU", "YOU_2012", "you-messe");
	}
	private function addEventBoard($id, $parentid, $kuerzel, $name, $desc, $wiki, $mlname) {
		$this->addGenericBoard($id, $parentid, "event.{$kuerzel}", $mlname, $wiki, $name, $desc);
	}

	private function getCommunityUser($address, $charset) {
		$mailto = iconv($address->getCharset(), $charset, $address->getAddress());
		list($name, $host) = explode("@", $mailto);
		if ($host == "community.junge-piraten.de") {
			return ucfirst($name);
		}
		if (!isset($this->mailusers[$mailto])) {
			if ($this->memcachelink == null) {
				$this->memcachelink = new Memcache;
				$this->memcachelink->pconnect("storage", 11211);
			}
			$this->mailusers[$mailto] = $this->memcachelink->get("nntpboard-communityuser-" . $mailto);

			if ($this->mailusers[$mailto] === false) {
				$ldaplink = Net_LDAP2::connect(array("binddn" => "cn=nntpboard,ou=community,o=Junge Piraten,c=DE", "bindpw" => $this->ldappass, "host" => "storage", "port" => 389) );
				$search = $ldaplink->search("ou=accounts,ou=community,o=Junge Piraten,c=DE", Net_LDAP2_Filter::create('mail', 'equals', $mailto), array("scope" => "one", "attributes" => array("uid")));
				if ($search->count() != 1) {
					$this->mailusers[$mailto] = null;
				} else {
					$this->mailusers[$mailto] = ucfirst($search->shiftEntry()->getValue("uid"));
				}
			}

			$this->memcachelink->set("nntpboard-communityuser-" . $mailto, $this->mailusers[$mailto], 0, 24*60*60);
		}
		return $this->mailusers[$mailto];
	}
	public function getAddressText($address, $charset) {
		$communityuser = $this->getCommunityUser($address, $charset);
		if ($communityuser != null) {
			return $communityuser;
		}
		$mailto = iconv($address->getCharset(), $charset, $address->getAddress());
		list($name, $host) = explode("@", $mailto);
		if ($host == "junge-piraten.de") {
			return ucwords(str_replace("."," ",$name));
		}
		return ($address->hasName() ? $address->getName() . " " : "") . "<" . $name . "@...>";
	}
	public function getAddressLink($address, $charset) {
		$communityuser = $this->getCommunityUser($address, $charset);
		if ($communityuser != null) {
			return "http://wiki.junge-piraten.de/wiki/Benutzer:" . $communityuser;
		}
		return "";
	}
	public function getAddressImage($address, $charset) {
		$communityuser = $this->getCommunityUser($address, $charset);
		if ($communityuser != null) {
			return "jupisavatar.php?name=" . urlencode($communityuser);
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
