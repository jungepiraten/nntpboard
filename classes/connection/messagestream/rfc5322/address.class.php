<?php

require_once(dirname(__FILE__) . "/../../../address.class.php");

class RFC5322Address extends Address {
	public static function parseObject($addr) {
		return new RFC5322Address($addr->getName(), $addr->getAddress(), $addr->getComment() );
	}

	public static function parsePlain($addr, $charset = "UTF-8") {
		$name = null;
		$comment = null;
		if (preg_match('/^(.*) \((.*?)\)\s*$/', $addr, $m)) {
			array_shift($m);
			$addr = trim(array_shift($m));
			$comment = trim(array_shift($m));
		}
		if (preg_match('/^(.*) <(.*)>\s*$/', $addr, $m)) {
			array_shift($m);
			$name = stripslashes(trim(array_shift($m)," \"'\t"));
			$addr = trim(array_shift($m));
		}
		return new RFC5322Address($name, trim($addr, "<>"), $comment, $charset);
	}

	public function getObject() {
		return $this;
	}

	public function getPlain() {
		return	($this->hasName() ? "{$this->getName()} <{$this->getAddress()}>" : $this->getAddress()) .
			($this->hasComment() ? " ({$this->getComment()})" : "");
	}
}
