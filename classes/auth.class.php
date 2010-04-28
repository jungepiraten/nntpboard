<?php

interface Auth {
	public function isAnonymous();
	public function getAddress();
	public function getNNTPUsername();
	public function getNNTPPassword();
}

abstract class AbstractAuth implements Auth {}

class AuthException extends Exception {}

?>
