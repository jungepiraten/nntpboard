<?php

// http://pear.php.net/package/Net_LDAP2
require_once("Net/LDAP2.php");

require_once(dirname(__FILE__)."/anon.class.php");
require_once(dirname(__FILE__)."/file.class.php");
require_once(dirname(__FILE__)."/../exceptions/auth.exception.php");

class JuPisAnonAuth extends AnonAuth {}

class JuPisAuth extends FileUserAuth {
	private $groups;

	public function __construct($config, $username, $password) {
		$this->groups = array();
		$dn = "uid=".$username.",ou=People,o=Junge Piraten,c=DE";
		$ldap = Net_LDAP2::connect(array("binddn" => $dn, "bindpw" => $password, "basedn" => "o=junge piraten,c=de", "host" => "storage"));
		foreach ($ldap->search("ou=Groups,o=Junge Piraten,c=DE", "(uniqueMember=".$dn.")", array("attributes" => array("cn"))) as $group_dn => $entry) {
			$this->groups[] = $entry->getValue("cn","single");
		}
		$user = $ldap->getEntry($dn, array("mail","uid","cn"));
		$displayName = $user->getValue("cn","single");
		$mail = $user->getValue("mail","single");
		if (empty($mail)) {
			$mail = $user->getValue("uid","single")."@community.junge-piraten.de";
		}
		parent::__construct($username, $password, new Address($displayName, $mail), str_replace(" ", "_", $username), $config->getNNTPPassword());
	}

	public function mayCancel($message) {
		return parent::mayCancel($message) or in_array("moderators", $this->groups);
	}
}

?>
