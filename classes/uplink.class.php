<?php

interface Uplink {
	public function open();
	public function close();

	public function getMessageIDs();
	public function getMessageCount();
	public function hasMessage($msgid);
	public function getMessage($msgid);

	public function post($message);
}

abstract class AbstractUplink implements Uplink {
	public function __construct() {}
}

?>
