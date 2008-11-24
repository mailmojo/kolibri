<?php
class PostgreSqlResultSet implements ArrayAccess, Iterator, Countable {
	private $position;
	private $resource;

	public function __construct ($resource) {
		$this->position = 0;
		$this->resource = $resource;
	}

	public function offsetExists ($offset) {
		return $offset > -1 && $offset < $this->count() ? true : false;
	}

	public function offsetGet ($offset) {
		return pg_fetch_assoc($this->resource, $offset);
	}

	public function offsetSet ($offset, $value) {
		throw new Exception('This result set is read-only.');
	}

	public function offsetUnset ($offset) {
		throw new Exception('This result set is read-only.');
	}

	public function count () {
		if (($numRows = pg_num_rows($this->resource)) != -1) {
			return $numRows;
		}
		throw new Exception('Error while trying to get number of rows in result set.');
	}

	public function current () {
		return $this->offsetGet($this->position);
	}

	public function key () {
		return $this->position;
	}

	public function next () {
		$this->position++;
	}

	public function rewind () {
		$this->position = 0;
	}

	public function valid () {
		return $this->offsetExists($this->position);
	}

	public function numAffectedRows () {
		return pg_affected_rows($this->resource);
	}

	/**
	 * Converts a value retrieved from the database into a more meaningfull type (i.e. textual
	 * boolean values into true boolean values).
	 * 
	 * @param string $value		The value to check/convert the type of.
	 * @return mixed	The value converted.
	 */
	public function convertType ($value) {
		if ($value == 't' || $value == 'f') {
			return $value == 't' ? true : false;
		}
		else if ($value == 'NULL') {
			return null;
		}
		return $value;
	}
}
?>
