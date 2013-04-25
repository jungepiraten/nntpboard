<?php

require_once(dirname(__FILE__) . "/../indexer.class.php");

class MySqlIndexer extends AbstractIndexer {
	private $mysql;

	public function __construct($host, $user, $pass, $name) {
		$this->mysql = new MySQLi($host, $user, $pass, $name);
	}

	public function search($term) {
		if (count($term) == 0) {
			return array();
		}

		$cond = array();
		foreach ($term as $t) {
			if (count($t) == 1) {
				$cond[] = "(`term` LIKE '%" . $this->mysql->real_escape_string($t[0]) . "%')";
			} else if (count($t) == 2) {
				$cond[] = "(`field` = '" . $this->mysql->real_escape_string($t[0]) . "' and `term` LIKE '%" . $this->mysql->real_escape_string($t[1]) . "%')";
			} else {
				// Tokenizer failed
				throw new Exception("Tokenizer Failed");
			}
		}
		$cond = implode(" or ", $cond);

		// Important: If we have redundant terms our COUNT-check won't return any results!
		$neededmatches = count($term);
		$result = $this->mysql->query("SELECT `boardid`, `messageid` FROM `indexer` WHERE " . $cond . " GROUP BY `boardid`, `messageid` HAVING COUNT(`id`) >= " . $neededmatches . " ORDER BY COUNT(`id`) DESC");
		$results = array();
		while ($row = $result->fetch_assoc()) {
			$results[] = new IndexerResult($row["boardid"], $row["messageid"]);
		}
		return $results;
	}

	protected function addTerm($field, $term, $boardid, $messageid) {
		$this->mysql->query("INSERT INTO `indexer` (`field`, `term`, `boardid`, `messageid`) VALUES ('" . $this->mysql->real_escape_string($field) . "', '" . $this->mysql->real_escape_string($term) . "', '" . $this->mysql->real_escape_string($boardid) . "', '" . $this->mysql->real_escape_string($messageid) . "')");
	}

	public function removeMessage($boardid, Message $message) {
		$this->mysql->query("DELETE FROM `indexer` WHERE `boardid` = '" . $this->mysql->real_escape_string($boardid) . "' and `messageid` = '" . $this->mysql->real_escape_string($message->getMessageID()) . "'");
	}
}

?>
