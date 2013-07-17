<?php

require_once(dirname(__FILE__)."/user.class.php");

if (!function_exists("mkdir_parents")) {
	function mkdir_parents($dir) {
		if (!file_exists($dir)) {
			if (!file_exists(dirname($dir))) {
				mkdir_parents(dirname($dir));
			}
			return mkdir($dir);
		}
	}
}

abstract class AbstractFileAuth extends AbstractUserAuth {
	private $data;

	public function __construct($username, $password, $address, $nntpusername, $nntppassword) {
		/* Konstruktor nicht andersherum eintragen, da sonst getFilename()
		 * ohne $this->username aufgerufen wird => ungut */
		parent::__construct($username, $password, $address, $nntpusername, $nntppassword);
		$this->loadData();
	}

	private function getFilename() {
		return dirname(__FILE__) . "/file/" . $this->getUsername() . ".dat";
	}

	private function loadData() {
		$filename = $this->getFilename();
		if (!file_exists($filename)) {
			// Der Benutzer ist bisher unbekannt - Also Dummy-Werte
			$this->data = array();
			return;
		}
		$this->data = unserialize(file_get_contents($this->getFilename()));
	}

	private function saveData() {
		$filename = $this->getFilename();
		mkdir_parents(dirname($filename));
		file_put_contents($filename, serialize($this->data));
	}

	protected function loadReadDate() {
		if (isset($this->data["readdate"])) {
			return $this->data["readdate"];
		}
		return parent::loadReadDate();
	}

	protected function loadReadThreads() {
		if (isset($this->data["readthreads"])) {
			return $this->data["readthreads"];
		}
		return parent::loadReadThreads();
	}

	protected function loadReadGroups() {
		if (isset($this->data["readgroups"])) {
			return $this->data["readgroups"];
		}
		return parent::loadReadGroups();
	}

	protected function saveReadDate($date) {
		$this->data["readdate"] = $date;
	}

	protected function saveReadThreads($data) {
		$this->data["readthreads"] = $data;
	}

	protected function saveReadGroups($data) {
		$this->data["readgroups"] = $data;
	}

	public function saveRead() {
		parent::saveRead();
		$this->saveData();
	}
}

?>
