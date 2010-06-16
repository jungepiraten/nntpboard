<?php

require_once(dirname(__FILE__)."/classes/address.class.php");
require_once(dirname(__FILE__)."/classes/message.class.php");

require_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__)."/classes/session.class.php");
$session = new Session($config);
$template = $config->getTemplate($session->getAuth());

$boardid = stripslashes($_REQUEST["boardid"]);
$reference = null;
if (!empty($_REQUEST["quote"])) {
	$reference = stripslashes($_REQUEST["quote"]);
}
if (!empty($_REQUEST["reply"])) {
	$reference = stripslashes($_REQUEST["reply"]);
}
if (!empty($_REQUEST["reference"])) {
	$reference = stripslashes($_REQUEST["reference"]);
}
$quote = isset($_REQUEST["quote"]);

$board = $config->getBoard($boardid);
if ($board === null) {
	$template->viewexception(new Exception("Board nicht gefunden!"));
}

$connection = $board->getConnection($session->getAuth());
if ($connection === null) {
	$template->viewexception(new Exception("Board enthaelt keine Group!"));
}

if ($reference !== null) {
	$connection->open();
	$group = $connection->getGroup();
	$reference = $group->getMessage($reference);
	$connection->close();
}

function generateMessage($config, $session, $reference) {
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

	$textbody = (!empty($_REQUEST["body"]) ? stripslashes($_REQUEST["body"]) : null);

	return new Message($messageid, time(), $autor, $subject, $charset, $parentid,  $textbody);
}

$preview = null;
if (isset($_REQUEST["preview"])) {
	$preview = generateMessage($config, $session, $reference);
}

if (isset($_REQUEST["post"])) {
	// TODO Sperre gegen F5
	$message = generateMessage($config, $session, $reference);

	try {
		$connection->open();
		$connection->post($message);
		$group = $connection->getGroup();
		$thread = $group->getThread($message->getMessageID());
		$connection->close();
		if ($board->isModerated($auth)) {
			$template->viewpostmoderated($board, $thread, $message);
		} else {
			$template->viewpostsuccess($board, $thread, $message);
		}
	} catch (PostingException $e) {
		$template->viewexception($e);
	}
}

$template->viewpostform($board, $reference, $quote, $preview);

?>
