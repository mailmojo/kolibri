<?php
class SqlException extends Exception {
	private $query;
	public function __construct ($message, $query, $code = 0) {
		parent::__construct($message, $code);
		$this->query = $query;
	}

	public function __toString () {
		return "exception '" . __CLASS__ . "' with message '" . $this->getMessage() . "' while "
			. "executing query '" . $this->query . "'";
	}
}
?>
