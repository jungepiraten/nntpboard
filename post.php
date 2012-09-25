<?php
require_once(dirname(__FILE__)."/classes/address.class.php");
require_once(dirname(__FILE__)."/classes/message.class.php");
require_once(dirname(__FILE__)."/config.inc.php");
require_once(dirname(__FILE__)."/classes/session.class.php");

$session = new Session($config);
$template = $config->getTemplate($session->getAuth());
$boardid = stripslashes($_REQUEST["boardid"]);
$reference = null;
$referencemessages = null;

if (!empty($_REQUEST["quote"])) {
	$reference = $config->decodeMessageID(stripslashes($_REQUEST["quote"]));
}

if (!empty($_REQUEST["reply"])) {
	$reference = $config->decodeMessageID(stripslashes($_REQUEST["reply"]));
}

if (!empty($_REQUEST["reference"])) {
	$reference = $config->decodeMessageID(stripslashes($_REQUEST["reference"]));
}

$quote = isset($_REQUEST["quote"]);
$board = $config->getBoard($boardid);

if ($board === null) {
	$template->viewexception(new Exception("Board nicht gefunden!"));
}

if (!$board->mayPost($session->getAuth())) {
	$template->viewexception(new Exception("Keine Berechtigung!"));
}

$connection = $board->getConnection();
if ($connection === null) {
	$template->viewexception(new Exception("Board enthaelt keine Group!"));
}

if ($reference !== null) {
	$connection->open($session->getAuth());
	$group = $connection->getGroup();
	$referencemessages = array();

	foreach (array_slice(array_reverse($group->getThread($reference)->getMessageIDs()),0,5) as $messageid) {
		$referencemessages[] = $group->getMessage($messageid);
	}

	$group->getThread($reference);
	$reference = $group->getMessage($reference);
	$connection->close();
}

function generateMessage($config, $session, $board, $reference) {
	$messageid = $config->generateMessageID();
	$subject = (!empty($_REQUEST["subject"]) ? trim(stripslashes($_REQUEST["subject"])) : null);

	if($session->getAuth()->isAnonymous()) {
		$author = new Address(trim(stripslashes($_REQUEST["user"])), trim(stripslashes($_REQUEST["email"])));
	} else {
		$author = $session->getAuth()->getAddress();
	}

	$storedattachments = isset($_REQUEST["storedattachment"]) && is_array($_REQUEST["storedattachment"]) ? $_REQUEST["storedattachment"] : array();
	$attachment = $_FILES["attachment"];

	if ($reference !== null) {
		$parentid = $reference->getMessageID();
	} else {
		$parentid = null;
	}

	if (empty($_REQUEST["body"])) {
		throw new Exception("Body empty");
	}

	$textbody = stripslashes($_REQUEST["body"]);
	$message = new Message($messageid, time(), $author, $subject, $parentid, $textbody);

	// Speichere alte Attachments und fuege aus allen die Message zusammen
	$as = array();
	foreach ($storedattachments as $partid) {
		$as[] = $session->getAttachment($partid);
	}

	$session->clearAttachments();
	foreach ($as as $a) {
		$message->addAttachment($a);
		$session->addAttachment($a);
	}

	// Fuege neue Attachments ein
	if ($attachment !== null) {
		for ($i = 0; $i < count($attachment["name"]); $i++) {

			// TODO Fehlerbehandlung
			if ($attachment["error"][$i] != 0) {
				continue;
			}

			$a = new Attachment("attachment", $attachment["type"][$i], file_get_contents($attachment["tmp_name"][$i]), basename($attachment["name"][$i]));
			// TODO Attachment-Whitelist
			if (!$config->isAttachmentAllowed($board, $m, $a)) {
				continue;
			}

			$message->addAttachment($a);
			$session->addAttachment($a);
		}
	}

	return $message;
}

$preview = null;
if (isset($_REQUEST["preview"])) {
	$preview = generateMessage($config, $session, $reference);
}

if (isset($_REQUEST["post"])) {
	try {
		// TODO Sperre gegen F5
		$message = generateMessage($config, $session, $board, $reference);

		$connection->open($session->getAuth());
		$resp = $connection->postMessage($message);
		$group = $connection->getGroup();
		$thread = $group->getThread($message->getMessageID());
		$connection->close();

		if ($resp == "m") {
			$template->viewpostmoderated($board, $thread, $message);
		} else {
			$template->viewpostsuccess($board, $thread, $message);
		}

		// Alte Attachments loeschen - werden ja nur fuers Preview gespeichert
		$session->clearAttachments();
	} catch (PostingException $e) {
		$template->viewexception($e);
	} catch (Exception $e) {
		$template->viewexception($e);
	}
}

$template->viewpostform($board, $referencemessages, $reference, $quote, $preview, $session->getAttachments());
?>
