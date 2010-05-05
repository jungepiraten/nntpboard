<?php

interface CacheProvider {
	public function open();
	public function close();

	public function getMessageIDs();
	public function getMessageCount();
	public function hasMessage($messageid);
	public function getMessage($messageid);
	public function addMessage($message, $threadid);
	public function removeMessage($messageid);

	public function getThreadIDs();
	public function getThreadCount();
	public function hasThread($messageid);
	public function getThread($messageid);

	public function getQueue();
	public function getQueueLength();
	public function hasQueued($messageid);
	public function addToQueue($message);
	public function removeFromQueue($messageid);

	public function sort();
}

abstract class AbstractCacheProvider implements CacheProvider {
	public function __construct() {}
}

?>
