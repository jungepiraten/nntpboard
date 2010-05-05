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
$connection = $group->getConnection($session->getAuth());

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
	$autor = $session->getAuth()->isAnonymous()
		? new Address(trim(stripslashes($_REQUEST["user"])), trim(stripslashes($_REQUEST["email"])))
		: $session->getAuth()->getAddress();
	$charset = (!empty($_REQUEST["charset"]) ? trim(stripslashes($_REQUEST["charset"])) : $config->getCharSet());
	
	if ($reference !== null) {
		$parentid = $reference->getMessageID();
	} else {
		$parentid = null;
	}

	$message = new Message($group->getGroup(), $articlenum, $messageid, time(), $autor, $subject, $charset, $parentid,  $mime = null);

	/* Wir nutzen vorerst nur text/plain -Nachrichten */
	$disposition = "inline";
	$mimetype = "text/plain";
	$text = (!empty($_REQUEST["body"]) ? stripslashes($_REQUEST["body"]) : null);

	$bodypart = new BodyPart($message, $disposition, $mimetype, $text, $charset);
	$message->addBodyPart($bodypart);
	
	try {
		$connection->open();
		$connection->post($message);
		$thread = $connection->getThread($message->getMessageID());
		$connection->close();
		if ($group->isModerated()) {
			$template->viewpostmoderated($board, $thread, $message);
		} else {
			$template->viewpostsuccess($board, $thread, $message);
		}
	} catch (PostingNotAllowedException $e) {
		$template->viewexception($e);
	}
}

$template->viewpostform($board, $reference);

?>
