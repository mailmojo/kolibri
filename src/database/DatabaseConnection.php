<?php
require(ROOT . '/database/ObjectBuilder.php');

/**
 * This abstract class defines the methods used to communicate with a database, and with it the
 * contract specific database connection implementations must follow.
 *
 * Clients will usually use one of the <code>getObject()</code>, <code>getObjects()</code> and
 * <code>getColumn()</code> methods, although <code>query()</code> is availible for direct
 * access to the result set object (which can be iterated as if it was an array).
 *
 * Every method which accepts a query string and parameters works in the same way. If the
 * parameters is an array, the query must use ? as placeholders. If however the parameters is an
 * object, you map placeholders to properties in the object as the following example shows:
 *
 *   SELECT username, name FROM users WHERE username = :username AND password = :password
 * 
 * Methods accepting class names to specify object nesting conforms to the rules of
 * <code>ObjectBuilder</code>. See its class documentation for details and examples.
 */
abstract class DatabaseConnection {
	/**
	 * The actual connection to the database.
	 */
	protected $connection;

	/**
	 * The result set from the last <code>query()</code>.
	 * @param ResultSet
	 */
	protected $resultSet;

	/**
	 * Creates a new instance of this class. No connection to the database will be established before
	 * calling <code>connect()</code> or executing the first query.
	 *
	 * @param array $conf Implementation-specific database configuration.
	 */
	abstract public function __construct ($conf);

	/**
	 * Connects to the database described by this object. An exception is thrown if a connection
	 * could not be established.
	 *
	 * @throws Exception If a connection could not be established.
	 * @return bool      <code>TRUE</code> upon successful connection.
	 */
	abstract public function connect ();

	/**
	 * Begins a new transaction.
	 *
	 * @return bool <code>TRUE</code> if a transaction was started, <code>FALSE</code> if not (i.e.
	 *              the connection is in an error state.
	 */
	abstract public function begin ();

	/**
	 * Commits or rolls back the active transaction, if any. The transaction is rolled back if in an
	 * invalid state, else it is commited.
	 *
	 * @return bool <code>TRUE</code> if transaction was commited, <code>FALSE</code> if rolled back.
	 */
	abstract public function commit ();

	/**
	 * Rolls back the active transaction.
	 */
	abstract public function rollback ();

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
	abstract public function query ($query, $params = null);

	/**
	 * Returns the last auto-incremented ID generated from this connection.
	 *
	 * @return int Last inserted auto-ID.
	 */
	abstract public function lastInsertId ();

	/**
	 * Escapes a value to make it safe for use in SQL queries. Only used internally.
	 * 
	 * Specific implementations can choose to convert types as needed as well, such as booleans for
	 * databases having native boolean support.
	 *
	 * @param mixed $value Data value to escape and/or convert.
	 * @return string      The value prepared for insertion into a SQL query.
	 */
	abstract protected function escapeValue ($value);

	/**
	 * Executes a query and returns the value from a specific column in the first result row. If no
	 * column name is supplied, the value from the first column is returned.
	 *
	 * @param string $query  The query to execute.
	 * @param mixed $params  Parameters to interpolate into query.
	 * @param string $column Name of column to retrieve values from. Defaults to first column if
	 *                       not specified.
	 * @return mixed         The data found, or <code>FALSE</code> if no rows were returned.
	 */
	public function getColumn ($query, $params = null, $column = null) {
		$result = $this->query($query, $params);
		if ($result && $result->valid()) {
			$row = $result->current();
			return !$column ? current($row) : $row[$column];
		}
		return false;
	}

	/**
	 * Executes a query and returns an object populated with data from the result.
	 *
	 * @param mixed $classes Class name of the object to create, or array structure specifying the
	 *                       object hierarchy to create. See <code>ObjectBuilder</code> for details.
	 * @param string $query  The query to execute.
	 * @param mixed $params  Parameters to interpolate into query.
	 * @return object        The populated object, or <code>FALSE</code> if no rows were returned.
	 */
	public function getObject ($classes, $query, $params = null) {
		if (is_array($classes)) {
			// The first element is the main class name
			$mainClass = array_shift($classes);
			$object = new $mainClass();
		}
		else {
			$object = new $classes();
			$classes = null;
		}

		$result = $this->query($query, $params);
		if ($result) {
			$sofa = new ObjectBuilder($result);
			return $sofa->fetchInto($object, $classes);
		}
		return false;
	}

	/**
	 * Executes a query and returns an array of objects populated with data from the result.
	 *
	 * @param mixed $classes Class name of the objects to create, or array structure specifying the
	 *                       object hierarchy to create. See <code>ObjectBuilder</code> for details.
	 * @param string $query  The query to execute.
	 * @param mixed $params  Parameters to interpolate into query.
	 * @return array         Array with the populated objects, or <code>FALSE</code> if an error
	 *                       occured.
	 */
	public function getObjects ($classes, $query, $params = null) {
		$result = $this->query($query, $params);
		if ($result) {
			$sofa = new ObjectBuilder($result);
			return $sofa->build($classes);
		}
		return false;
	}

	/**
	 * Returns the number of rows affected by the last INSERT, UPDATE or DELETE queries.
	 *
	 * @return int Number of affected rows.
	 */
	public function numAffectedRows () {
		return $this->resultSet->numAffectedRows();
	}

	/**
	 * Prepares and returns the query by escaping and interpolating any parameters into the query.
	 *
	 * @param string $query The query to prepare.
	 * @param array $params Parameters to escape and interpolate into query.
	 * @return string       The query prepared to be executed.
	 */
	protected function prepareQuery ($query, $params) {
		if (!empty($params)) {
			if (is_array($params)) {
				$escapedParams = array_map(array($this, 'escapeValue'), $params);
				
				/*
				 * When params is a simple array, we expect ?-placeholders. Convert them to %s in order
				 * to simply use vsprintf().
				 */
				$transformedQuery = str_replace('?', '%s', $query);
				$preparedQuery = vsprintf($transformedQuery, $escapedParams);

				if (!$preparedQuery) {
					throw new Exception('Number of replacement chars and parameter values does not match');
				}
				return $preparedQuery;
			}
			else if (is_object($params)) {
				/*
				 * Regexp to match placeholders according to the rules of PHP variables, and excluding
				 * double colons :: which indicates a cast in SQL and is thus not a placeholder.
				 */
				$allowedChars = '/[^:]:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/';
				$matches = array();
				preg_match_all($allowedChars, $query, $matches);

				// Loop through each match, and remember those that actually exists among the parameters
				foreach ($matches[1] as $match) {
					if (property_exists($params, $match)) {
						$patterns[] = "/[^:]:$match/";
						$replace[] = $this->escapeValue($params->$match);
					}
					else {
						throw new Exception("No property in parameter object matches the named parameter $match");
					}
				}

				// Do the actual interpolation
				return preg_replace($patterns, $replace, $query);
			}
		}

		return $query;
	}
}
