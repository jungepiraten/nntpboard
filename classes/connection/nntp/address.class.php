<?php

require_once(dirname(__FILE__) . "/../../address.class.php");

class NNTPAddress extends Address {
	public static function parseObject($addr) {
		return new NNTPAddress($addr->getName(), $addr->getAddress(), $addr->getComment());
	}
	
	public static function parsePlain($addr) {
		if (preg_match('/^(.*) \((.*?)\)\s*$/', $addr, $m)) {
			array_shift($m);
			$addr = trim(array_shift($m));
			$comment = trim(array_shift($m));
		}
		if (preg_match('/^(.*) <(.*)>\s*$/', $addr, $m)) {
			array_shift($m);
			$name = trim(array_shift($m)," \"'\t");
			$addr = trim(array_shift($m));
		}
		return new NNTPAddress($name, trim($addr, "<>"), $comment);
	}

	public function getObject() {
		return $this;
	}

	public function getPlain() {
		return ($this->hasName() ? "{$this->getName()} <{$this->getAddress()}>" : $this->getAddress()) . ($this->hasComment() ? " ({$this->getComment()})" : "");
	}
}
