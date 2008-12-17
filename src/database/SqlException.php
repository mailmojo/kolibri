<?php
require(ROOT . '/database/DatabaseException.php');

/**
 * This is a specific database exception for errors related to the execution of SQL queries.
 */
class SqlException extends DatabaseException {
	/**
	 * The query which triggered the error.
	 * @var string
	 */
	private $query;

	/**
	 * Constructor.
	 */
	public function __construct ($message, $query, $code = 0) {
		parent::__construct($message, $code);
		$this->query = $query;
	}

	/**
	 * Returns the query that caused this exception.
	 *
	 * @return string
	 */
	public function getQuery () {
		return $this->query;
	}

	/**
	 * Returns a string representation of this exception.
	 *
	 * @return string
	 */
	public function __toString () {
		return "exception '" . __CLASS__ . "' with message '{$this->getMessage()}' while "
			. "executing query:\n{$this->getQuery()}\n in {$this->getFile()}:{$this->getLine()}\n"
			. "Stack trace:\n" . $this->getTraceAsString();
	}
}
?>
