<?php
/**
 * This class extends the <code>DatabaseConnection</code> class to support connections to a
 * SQLite database. This class may also implement SQLite-specific features.
 *
 * See the class documentation in <code>DatabaseConnection</code> for details.
 */
class SqliteConnection extends DatabaseConnection {
	private $database;
	private $inTransaction;
	private $transactionInError;

	/**
	 * Creates a new instance of this class. No connection to the database will be established before
	 * calling <code>connect()</code> or executing the first query.
	 *
	 * @param array $conf Database configuration.
	 */
	public function __construct ($conf) {
		$this->database   = $conf['database'];
	}

	/**
	 * Opens a connection to the SQLite database. This will attempt to create the database if it
	 * does not exist.
	 */
	public function connect () {
		$this->connection = sqlite_factory($this->database);

		if (!$this->connection) {
			throw new DatabaseException('Could not connect to the database');
		}
		return true;
	}

	/**
	 * Begins a new transaction.
	 *
	 * @return bool <code>TRUE</code> if a transaction was started, <code>FALSE</code> if an
	 *              error occured.
	 */
	public function begin () {
		if (!$this->inTransaction) {
			if (!$this->connection) {
				$this->connect();
			}

			$this->inTransaction = $this->connection->queryExec('BEGIN');
			$this->transactionInError = false;
		}
		return $this->inTransaction;
	}

	/**
	 * Commits or rolls back the active transaction, if any. The transaction is rolled back if in an
	 * invalid state, else it is commited.
	 *
	 * @return bool <code>TRUE</code> if transaction was commited, <code>FALSE</code> if rolled back.
	 */
	public function commit () {
		if ($this->inTransaction) {
			$transactionInError = $this->transactionInError;
			$this->inTransaction = false;
			$this->transactionInError = false;

			if (!$transactionInError) {
				$this->connection->queryExec('COMMIT');
				return true;
			}
			else {
				$this->connection->queryExec('ROLLBACK');
				return false;
			}
		}
	}

	/**
	 * Rolls back the active transaction.
	 */
	public function rollback () {
		if ($this->inTransaction) {
			$this->connection->queryExec('ROLLBACK');
		}
	}

	/**
	 * Sends a query to the database after escaping and interpolating the supplied parameters, and
	 * returns the result set.
	 *
	 * If a connection to the database is not yet established, <code>connect()</code> is called
	 * implicitly. The same is true of transactions; if a transaction has not yet been started on the
	 * connection, <code>begin()</code> is called.
	 *
	 * @param string $query The query to execute.
	 * @param mixed $params Parameters to interpolate into query.
	 * @throws Exception    Upon an error when executing the query.
	 * @return ResultSet    Representing the query results. Implementation-specific.
	 */
	public function query ($query, $params = null) {
		if (!$this->connection) {
			$this->connect();
		}

		// We must discard any previous results to free up any locks still held
		if ($this->resultSet !== null) {
			$this->resultSet = null;
		}

		if ($this->transactionInError) {
			return false;
		}
		else if (!$this->inTransaction) {
			// No transaction yet started, let's begin one
			$this->begin();
		}

		// Interpolate any parameters into query
		$preparedQuery = $this->prepareQuery($query, $params);

		$error = null;
		if ($result = $this->connection->unbufferedQuery($preparedQuery, SQLITE_ASSOC, $error)) {
			$this->resultSet = new SqliteResultSet($this, $result);
			return $this->resultSet;
		}

		$this->rollback();
		// XXX: Should perhaps pass some kind of SQL error state as code
		throw new SqlException($error, $preparedQuery);
	}

	/**
	 * Returns the native database connection. Used internally by <code>SqlResultSet</code> which
	 * required the connection for some of its functionality.
	 *
	 * @return SQLiteDatabase
	 */
	public function getNativeConnection () {
		return $this->connection;
	}

	/**
	 * Escapes a value to make it safe for use in SQL queries.
	 * 
	 * Converts null to SQL NULL string, boolean values to accepted string representations, and
	 * escapes necessary characters in strings. Pure numeric values are simply returned as is.
	 *
	 * TODO: Research this and isPureNumber() as it relates to SQLite.
	 *
	 * @param mixed $value Data value to escape and/or convert.
	 * @return string      The value prepared for insertion into a SQL query.
	 */
	protected function escapeValue ($value) {
		if ($value === NULL) {
			return 'NULL';
		}
		if (is_bool($value)) {
			return ($value ? 1 : 0);
		}
		if ($this->isPureNumber($value)) {
			return $value;
		}

		if (get_magic_quotes_gpc()) {
			$value = stripslashes($value);
		}
		return "'" . sqlite_escape_string($value) . "'";
	}

	/**
	 * Stricter type check on numbers. Solves one specific problem where the string value contains
	 * prefixing zeroes, which most likely means it shouldn't be treated as a number but a string of
	 * digits. Ie. telephone numbers, hexadecimal strings, postal codes etc.
	 */
	private function isPureNumber ($value) {
		// Only values considered numeric by PHP are pure numbers
		if (!is_numeric($value)) {
			return false;
		}

		// If the value is an actual int or float type variable it's a pure number
		if (is_int($value) || is_float($value)) {
			return true;
		}

		// If it contains a decimal point, it's considered a pure number
		if (strpos($value, '.') !== false) {
			return true;
		}

		// If an integer cast does not change the number length, it's considered pure (ie. no leading zeroes)
		if (strlen((int) $value) == strlen($value)) {
			return true;
		}

		return false;
	}
}
?>
