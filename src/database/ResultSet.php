<?php
/**
 * This abstract class defines the methods used to retrieve data from a database result, and with
 * it the contract specific database result set implementations must follow.
 */ 
abstract class ResultSet implements ArrayAccess, Iterator, Countable {
	/**
	 * Current row position.
	 * @var int
	 */
	protected $position;

	/**
	 * The actual result resource or object as implemented by the PHP database module used.
	 * @var mixed
	 */
	protected $resource;

	/**
	 * Creates a new result set backed by the supplied data.
	 *
	 * @param mixed $resource Actual result resource or object.
	 */
	abstract public function __construct ($resource);

	/**
	 * Returns the number of rows affected by the query which produced this result. Only relevant
	 * after INSERT, UPDATE or DELETE queries.
	 *
	 * @return int Number of affected rows.
	 */
	abstract public function numAffectedRows ();

	/**
	 * Converts the supplied value, as returned from the database, to a PHP data type.
	 *
	 * @param mixed $value Value as returned from the database.
	 * @return mixed       The value converted to a matching PHP data type.
	 */
	abstract public function convertType ($value);

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
