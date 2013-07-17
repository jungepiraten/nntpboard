<?php

require_once(dirname(__FILE__)."/../auth.class.php");

abstract class AbstractUserAuth extends AbstractAuth {
	private $username;
	private $password;
	private $nntpusername;
	private $nntppassword;
	private $address;

	public function __construct($username, $password, $address, $nntpusername, $nntppassword) {
		parent::__construct();
		$this->username = $username;
		$this->password = $password;
		$this->address = $address;
		$this->nntpusername = $nntpusername;
		$this->nntppassword = $nntppassword;
	}

	public function getUsername() {
		return $this->username;
	}

	public function getPassword() {
		return $this->password;
	}

	public function getAddress() {
		return $this->address;
	}

	public function getNNTPUsername() {
		return $this->nntpusername;
	}

	public function getNNTPPassword() {
		return $this->nntppassword;
	}
}
