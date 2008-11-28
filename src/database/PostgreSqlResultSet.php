<?php
/**
 * This class implements result sets from a PostgreSQL database.
 */
class PostgreSqlResultSet extends ResultSet {
	/**
	 * Caches the number of rows in this result set, after the first call to <code>count()</code>.
	 * @var int
	 */
	private $numRows;

	/**
	 * Creates a new result set backed by the supplied PostgreSQL result resource.
	 * 
	 * @param resource $resource Result resource.
	 */
	public function __construct ($resource) {
		$this->position = 0;
		$this->resource = $resource;
	}

	/**
	 * Returns the row at the specified offset as an associative array.
	 *
	 * @param int $offset Row to retrieve.
	 * @return array      The row found.
	 */
	public function offsetGet ($offset) {
		return pg_fetch_assoc($this->resource, $offset);
	}

	/**
	 * Returns the number of rows in this result set.
	 *
	 * @throws Exception If an error occured while retrieving the row count.
	 * @return int       Number of rows.
	 */
	public function count () {
		if (!isset($this->numRows)) {
			if (($this->numRows = pg_num_rows($this->resource)) == -1) {
				throw new Exception('Error while trying to get number of rows in result set');
			}
		}
		return $this->numRows;
	}

	/**
	 * Returns the number of rows affected by the query which produced this result. Only relevant
	 * after INSERT, UPDATE or DELETE queries.
	 *
	 * @return int Number of affected rows.
	 */
	public function numAffectedRows () {
		return pg_affected_rows($this->resource);
	}

	/**
	 * Converts the supplied value, as returned from the database, to a PHP data type. Specifically,
	 * textual boolean values are converted to true PHP boolean values.
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
