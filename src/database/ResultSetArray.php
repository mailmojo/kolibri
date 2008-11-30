<?php
/**
 * This is an abstract result set implementation that supports access to specific rows by accessing
 * the result set as it was an array.
 */ 
abstract class ResultSetArray implements ResultSet, ArrayAccess, Countable {
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
	 * Current row position.
	 * @var int
	 */
	protected $position;

	/**
	 * Checks of a row exists at the specified offset.
	 *
	 * @param mixed $offset Row number to check.
	 * @return bool         <code>TRUE</code> if the row exists, <code>FALSE</code> if not.
	 */
	public function offsetExists ($offset) {
		return $offset > -1 && $offset < $this->count() ? true : false;
	}

	/**
	 * Denies access to set rows into this result set.
	 */
	public function offsetSet ($offset, $value) {
		throw new Exception('This result set is read-only.');
	}

	/**
	 * Denies access to unset rows in this result set.
	 */
	public function offsetUnset ($offset) {
		throw new Exception('This result set is read-only.');
	}

	/**
	 * Returns the row at the current position.
	 *
	 * @return array Current row.
	 */
	public function current () {
		return $this->offsetGet($this->position);
	}

	/**
	 * Returns the row offset of the current position.
	 *
	 * @return int Current positon.
	 */
	public function key () {
		return $this->position;
	}

	/**
	 * Advances the current row position.
	 */
	public function next () {
		$this->position++;
	}

	/**
	 * Rewinds the current row position to the beginning.
	 */
	public function rewind () {
		$this->position = 0;
	}

	/**
	 * Checks to see if the current position is valid.
	 *
	 * @return bool
	 */
	public function valid () {
		return $this->offsetExists($this->position);
	}
}
?>
