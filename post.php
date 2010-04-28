<?php

require_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__)."/classes/smarty.class.php");
require_once(dirname(__FILE__)."/classes/session.class.php");
require_once(dirname(__FILE__)."/classes/address.class.php");
require_once(dirname(__FILE__)."/classes/message.class.php");
require_once(dirname(__FILE__)."/classes/bodypart.class.php");
$session = new Session($config);
$smarty = new PostSmarty($config, $session->getAuth());

$boardid = stripslashes($_REQUEST["boardid"]);
$reference = !empty($_REQUEST["reference"]) ? stripslashes($_REQUEST["reference"]) : null;

$board = $config->getBoard($boardid);
if ($board === null) {
	die("Board nicht gefunden!");
}

$group = $board->getGroup();
if ($group === null) {
	die("Board enthaelt keine Group!");
}
$connection = $group->getConnection($config->getDataDir(), $session->getAuth());
$connection->open();

if ($reference !== null) {
	$reference = $connection->getMessage($reference);
}

$connection->close();

if (isset($_REQUEST["post"])) {
	// TODO Sperre gegen F5
	// Die Artikelnummer wird erst durch den Newsserver zugewiesen
	$articlenum = null;
	// TODO MessageID generieren
	$messageid = "<".md5($subject."-".microtime(true)."-".rand(1000,9999))."@nntpboard>";
	$subject = (!empty($_REQUEST["subject"]) ? trim(stripslashes($_REQUEST["subject"])) : null);
	$autor = $session->getAuth()->getAddress();
	$charset = $config->getCharSet();
	
	if ($reference !== null) {
		$threadid = $reference->getThreadID();
		$parentid = $reference->getMessageID();
		$parentnum = $reference->getArticleNum();
	} else {
		$threadid = $messageid;
		$parentid = null;
		$parentnum = null;
	}

	$message = new Message($group->getGroup(), $articlenum, $messageid, time(), $autor, $subject, $charset, $threadid, $parentid, $parentnum, $mime = null);

	$disposition = "inline";
	$mimetype = "text/plain";
	$text = (!empty($_REQUEST["body"]) ? stripslashes($_REQUEST["body"]) : null);

	$bodypart = new BodyPart($message, 0, $disposition, $mimetype, $text, $charset);
	$message->addBodyPart($bodypart);
	
	try {
		$connection->open();
		$connection->post($message);
		$connection->close();
		$smarty->viewpostsuccess($board, $message);
	} catch (Exception $e) {
		// TODO Fehler anzeigen
		var_dump($e);
	}
}

$smarty->viewpostform($board, $reference);

?>
