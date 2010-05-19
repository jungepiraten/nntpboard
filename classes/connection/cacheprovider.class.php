<?php

interface CacheProvider {
	public function open();
	public function close();

	public function getMessageIDs();
	public function getMessageCount();
	public function hasMessage($messageid);
	public function getMessage($messageid);
	public function addMessage($message);
	public function removeMessage($message);
}

abstract class AbstractCacheProvider implements CacheProvider {
	public function __construct() {}
}

?>
