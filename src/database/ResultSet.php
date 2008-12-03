<?php
/**
 * This is the high-level interface defining the contract specific result set implementations
 * must follow.
 */ 
interface ResultSet extends Iterator {
	/**
	 * Constructor.
	 *
	 * @param DatabaseConnection $conn The database connection implementation.
	 * @param mixed $result            The actual data result set as issued by the PHP driver.
	 */
	public function __construct ($conn, $result);

	/**
	 * Returns the number of rows affected by the query which produced this result. Only relevant
	 * after INSERT, UPDATE or DELETE queries.
	 *
	 * @return int Number of affected rows.
	 */
	public function numAffectedRows ();

	/**
	 * Returns the last auto-incremented ID generated from an INSERT. Note that this is not always
	 * directly related to the result set. Depending on the implementation, this could return the
	 * last ID generated at this result set, or the last generated through the connection.
	 *
	 * @return int Last inserted auto-ID.
	 */
	public function lastInsertId ();

	/**
	 * Converts the supplied value, as returned from the database, to a PHP data type.
	 *
	 * @param mixed $value Value as returned from the database.
	 * @return mixed       The value converted to a matching PHP data type.
	 */
	public function convertType ($value);
}
?>
