<?php

require_once(dirname(__FILE__)."/classes/address.class.php");
require_once(dirname(__FILE__)."/classes/message.class.php");
require_once(dirname(__FILE__)."/classes/bodypart.class.php");

require_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__)."/classes/session.class.php");
$session = new Session($config);
$template = $config->getTemplate($session->getAuth());

$boardid = stripslashes($_REQUEST["boardid"]);
$reference = !empty($_REQUEST["reference"]) ? stripslashes($_REQUEST["reference"]) : null;

$board = $config->getBoard($boardid);
if ($board === null) {
	$template->showexception(new Exception("Board nicht gefunden!"));
}

$group = $board->getGroup();
if ($group === null) {
	$template->showexception(new Exception("Board enthaelt keine Group!"));
}
$connection = $group->getConnection($config->getDataDir(), $session->getAuth());

if ($reference !== null) {
	$connection->open();
	$reference = $connection->getMessage($reference);
	$connection->close();
}

if (isset($_REQUEST["post"])) {
	// TODO Sperre gegen F5

	// Die Artikelnummer wird erst durch den Newsserver zugewiesen
	$articlenum = null;
	$messageid = "<" . uniqid("", true) . "@" . $config->getMessageIDHost() . ">";
	$subject = (!empty($_REQUEST["subject"]) ? trim(stripslashes($_REQUEST["subject"])) : null);
	$autor = $session->getAuth()->getAddress();
	$charset = (!empty($_REQUEST["charset"]) ? trim(stripslashes($_REQUEST["charset"])) : $config->getCharSet());
	
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

	/* Wir nutzen vorerst nur text/plain -Nachrichten */
	$disposition = "inline";
	$mimetype = "text/plain";
	$text = (!empty($_REQUEST["body"]) ? stripslashes($_REQUEST["body"]) : null);

	$bodypart = new BodyPart($message, 0, $disposition, $mimetype, $text, $charset);
	$message->addBodyPart($bodypart);
	
	try {
		$connection->open();
		$connection->post($message);
		$connection->close();
		if ($group->isModerated()) {
			// TODO bestaetigung anzeigen oder so
		} else {
			$template->viewpostsuccess($board, $message);
		}
	} catch (PostingNotAllowedException $e) {
		$template->viewexception($e);
	}
}

$template->viewpostform($board, $reference);

?>
