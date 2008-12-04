<?php
/**
 * This class implements an unbuffered result set from a SQLite database. It practically simply
 * wraps <code>SQLiteUnbuffered</code>.
 */
class SqliteResultSet implements ResultSet {
	/**
	 * The database connection which instantiated this result set.
	 * @var DatabaseConnection
	 */
	protected $conn;

	/**
	 * The actual result resource or object as implemented by the PHP database module used.
	 * @var mixed
	 */
	protected $result;

	/**
	 * Internal row position.
	 * @var int
	 */
	private $position;

	/**
	 * Creates a new result set backed by the supplied SQLite result set.
	 * 
	 * @param SqliteConnection $conn   The database connection implementation.
	 * @param SQLiteUnbuffered $result Result set.
	 */
	public function __construct ($conn, $result) {
		$this->conn   = $conn;
		$this->result = $result;
	}

	/**
	 * Returns the current row.
	 *
	 * @return array Current row.
	 */
	public function current () {
		return $this->result->current();
	}

	/**
	 * Returns the current row number.
	 *
	 * @return int Row number.
	 */
	public function key () {
		return $this->position;
	}

	/**
	 * Advances the current row position.
	 */
	public function next () {
		if ($this->valid()) {
			$this->result->next();
			$this->position++;
		}
	}

	/**
	 * Rewinding this unbuffered result set is unsupported.
	 * 
	 * @throws Exception Always, as this is unsupported.
	 */
	public function rewind () {
		throw new DatabaseException('Rewinding a SqliteResultSet is not supported');
	}

	/**
	 * Checks to see if the current row position is valid.
	 *
	 * @return bool
	 */
	public function valid () {
		return $this->result->valid();
	}

	/**
	 * Returns the number of rows affected by the last executed query. Only relevant
	 * after INSERT, UPDATE or DELETE queries.
	 *
	 * @return int Number of affected rows.
	 */
	public function numAffectedRows () {
		return $this->conn->getNativeConnection()->changes();
	}

	/**
	 * Returns the row id of the last inserted row.
	 *
	 * @return int The value of the last auto-incremented row id.
	 */
	public function lastInsertId () {
		return $this->conn->getNativeConnection()->lastInsertRowid();
	}

	/**
	 * Converts the supplied value, as returned from the database, to a PHP data type. Specifically,
	 * textual boolean values are converted to true PHP boolean values.
	 * TODO: Do some research on this as it relates to SQLite. Currently this is a copy from Pg.
	 * 
	 * @param mixed $value Value as returned from the database.
	 * @return mixed       The value converted.
	 */
	public function convertType ($value) {
		if ($value == 't' || $value == 'f') {
			return $value == 't' ? true : false;
		}
		return $value;
	}
}
?>
