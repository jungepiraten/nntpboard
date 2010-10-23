<?php

require_once(dirname(__FILE__)."/exceptions/board.exception.php");
require_once(dirname(__FILE__)."/../libs/xtea.class.php");

abstract class DefaultConfig {
	private $boards;
	
	public function __construct() {}

	/**
	 * Board-Verwaltung
	 **/
	protected function addBoard($board) {
		if ($this->hasBoard($board->getParentID())) {
			$parent = $this->getBoard($board->getParentID());
			$board->setParent($parent);
			$parent->addSubBoard($board);
		}
		$this->boards[$board->getBoardID()] = $board;
	}
	public function hasBoard($id = null) {
		return isset($this->boards[$id]);
	}
	public function getBoard($id = null) {
		if (isset($this->boards[$id])) {
			return $this->boards[$id];
		}
		throw new NotFoundBoardException($id);
	}
	public function getBoardIDs() {
		return array_keys($this->boards);
	}
	
	/**
	 * Optionen
	 **/
	public function getCharset() {
		return "UTF-8";
	}

	public function getThreadsPerPage() {
		return 20;
	}
	public function getMessagesPerPage() {
		return 15;
	}

	/**
	 * Erweiterte Optionen
	 **/
	public function getAddressText($address, $charset) {
		return iconv($address->getCharset(), $charset, $address->__toString());
	}
	public function getAddressLink($address, $charset) {
		return "mailto:" . $address->getAddress();
	}
	public function getAddressImage($address, $charset) {
		return "images/genericperson.png";
	}
	
	abstract public function getAuth($user, $pass);
	abstract public function getAnonymousAuth();

	/**
	 * Branding / Style
	 **/
	public function getVersion() {
		// TODO es ist nicht umbedingt schoen, die Versionsnummer hier festzulegen ;)
		return "1.0RC2";
	}
	public function generateMessageID() {
		return base64_encode("<" . uniqid("", true) . "@" . $this->getMessageIDHost() . ">");
	}
	abstract protected function getMessageIDHost();
	abstract public function getTemplate($auth);
	public function isAttachmentAllowed($attachment) {
		// Per Default erlauben wir alle Attachments < 512 KB
		return $attachment->getLength() < 512 * 1024;
	}

	/**
	 * Funktionen zur Verschluesselung der Cookies
	 **/
	abstract protected function getSecretKey();
	private function getXTEA() {
		return new XTEA($this->getSecretKey());
	}
	public function encryptString($string) {
		return $this->getXTEA()->encrypt($string);
	}
	public function decryptString($string) {
		return $this->getXTEA()->decrypt($string);
	}
}

?>
