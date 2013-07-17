<?php

interface MessageStreamWriter {
	public function post(RFC5322Message $message);
}

abstract class AbstractMessageStreamWriter implements MessageStreamWriter {}
