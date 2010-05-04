<?php

class DataDirException extends Exception {
	private $filename;

	public function __construct($filename, $message = null) {
		parent::__construct($message);
		$this->filename = $filename;
	}
}

class CreationFailedDataDirException extends DataDirException {}
class InvalidDatafileDataDirException extends DataDirException {}

?>
