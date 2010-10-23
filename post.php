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
	$messageid = $config->generateMessageID();
	$subject = (!empty($_REQUEST["subject"]) ? trim(stripslashes($_REQUEST["subject"])) : null);
	$autor = $session->getAuth()->isAnonymous()
		? new Address(trim(stripslashes($_REQUEST["user"])), trim(stripslashes($_REQUEST["email"])))
		: $session->getAuth()->getAddress();
	$charset = (!empty($_REQUEST["charset"]) ? trim(stripslashes($_REQUEST["charset"])) : $config->getCharSet());
	$storedattachments = is_array($_REQUEST["storedattachment"]) ? $_REQUEST["storedattachment"] : array();
	$attachment = $_FILES["attachment"];
	
	if ($reference !== null) {
		$parentid = $reference->getMessageID();
	} else {
		$parentid = null;
	}

	$textbody = (!empty($_REQUEST["body"]) ? stripslashes($_REQUEST["body"]) : null);

	$message = new Message($messageid, time(), $autor, $subject, $charset, $parentid,  $textbody);
	// Speichere alte Attachments und 
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
			if (!$config->isAttachmentAllowed($a)) {
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
	// TODO Sperre gegen F5
	$message = generateMessage($config, $session, $reference);

	try {
		$connection->open();
		$resp = $connection->postMessage($message);
		$group = $connection->getGroup();
		$thread = $group->getThread($message->getMessageID());
		$connection->close();
		if ($resp == "m") {
			$template->viewpostmoderated($board, $thread, $message);
		} else {
			$template->viewpostsuccess($board, $thread, $message);
		}
	} catch (PostingException $e) {
		$template->viewexception($e);
	}
}

// See http://www.php.net/file-upload for details
function display_filesize($filesize){
   
    if(is_numeric($filesize)){
    $decr = 1024; $step = 0;
    $prefix = array('Byte','KB','MB','GB','TB','PB');
       
    while(($filesize / $decr) > 0.9){
        $filesize = $filesize / $decr;
        $step++;
    }
    return round($filesize,2).' '.$prefix[$step];
    } else {

    return 'NaN';
    }
   
}

// See http://www.php.net/ini_get for details
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    switch($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
}

$maxuploadsize = display_filesize(return_bytes(ini_get("upload_max_filesize")));

$template->viewpostform($board, $maxuploadsize, $reference, $quote, $preview, $session->getAttachments());

?>
