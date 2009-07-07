<?php
require(ROOT . '/database/ObjectBuilder.php');

/**
 * This abstract class defines the methods used to communicate with a database, and with it the
 * contract specific database connection implementations must follow. See specific database
 * connection implementations for details on configuration.
 *
 * Clients will usually use one of the <code>getObject()</code>, <code>getObjects()</code> and
 * <code>getColumn()</code> methods for SELECTs, although <code>query()</code> is availible for
 * direct access to the result set object (which can be iterated as if it was an array).
 * <code>query()</code> should always be used when issuing INSERT, UPDATE and DELETE queries.
 *
 * Every method which accepts a query string and parameters works in the same way. If the
 * parameters is an array, the query must use ? as placeholders. If however the parameters is an
 * object, you map placeholders to properties in the object as the following example shows:
 *
 *	 SELECT username, name FROM users WHERE username = :username AND password = :password
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
	 * @return bool		 <code>TRUE</code> upon successful connection.
	 */
	abstract public function connect ();

	/**
	 * Begins a new transaction.
	 *
	 * @return bool <code>TRUE</code> if a transaction was started, <code>FALSE</code> if not (i.e.
	 *				the connection is in an error state.
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
	 * @throws Exception	Upon an error when executing the query.
	 * @return ResultSet	Representing the query results. Implementation-specific.
	 */
	abstract public function query ($query, $params = array());

	/**
	 * Sends several queries (separated by semicolons) to the database, and returns the number
	 * of rows affected.
	 *
	 * This method doesn't support parameters, and thus will not automatically protect the
	 * queries from SQL injection. For dynamic queries with user-supplied values,
	 * <code>query()</code> should be used.
	 *
	 * @param string $query The query to execute.
	 * @throws Exception	Upon an error when executing the query.
	 * @return int			Number of rows affected by the queries.
	 */
	abstract public function batchQuery ($query);

	/**
	 * Escapes a value to make it safe for use in SQL queries. Only used internally.
	 * 
	 * Specific implementations can choose to convert types as needed as well, such as booleans for
	 * databases having native boolean support.
	 *
	 * @param mixed $value Data value to escape and/or convert.
	 * @return string	   The value prepared for insertion into a SQL query.
	 */
	abstract protected function escapeValue ($value);

	/**
	 * Executes a query and returns the value from a specific column in the first result row. If no
	 * column name is supplied, the value from the first column is returned.
	 *
	 * @param string $query  The query to execute.
	 * @param mixed $params  Parameters to interpolate into query.
	 * @param string $column Name of column to retrieve values from. Defaults to first column if
	 *						 not specified.
	 * @return mixed		 The data found, or <code>NULL</code> if no rows were found.
	 */
	public function getColumn ($query, $params = array(), $column = null) {
		$result = $this->query($query, $params);
		if ($result->valid()) {
			$row = $result->current();
			return !$column ? current($row) : $row[$column];
		}
		return null;
	}

	/**
	 * Executes a query and returns the first row as an array. If no rows are found, an empty
	 * array is returned.
	 *
	 * @param string $query The query to execute.
	 * @param mixed $params Parameters to interpolate into query.
	 * @return array		First row of the result where the keys equal the columns.
	 */
	public function getRow ($query, $params = array()) {
		$result = $this->query($query, $params);
		if ($result->valid()) {
			return $result->current();
		}
		return array();
	}

	/**
	 * Executes a query and returns an object populated with data from the result.
	 *
	 * @param mixed $classes Class name of the object to create, or array structure specifying the
	 *						 object hierarchy to create. See <code>ObjectBuilder</code> for details.
	 * @param string $query  The query to execute.
	 * @param mixed $params  Parameters to interpolate into query.
	 * @return object		 The populated object, or <code>NULL</code> if no rows were found.
	 */
	public function getObject ($classes, $query, $params = array()) {
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
		$sofa = new ObjectBuilder($result);
		if ($sofa->fetchInto($object, $classes)) {
			return $object;
		}
		return null;
	}

	/**
	 * Executes a query and returns an array of objects populated with data from the result.
	 *
	 * @param mixed $classes Class name of the objects to create, or array structure specifying the
	 *						 object hierarchy to create. See <code>ObjectBuilder</code> for details.
	 * @param string $query  The query to execute.
	 * @param mixed $params  Parameters to interpolate into query.
	 * @return array		 Array with the populated objects, or <code>FALSE</code> if an error
	 *						 occured.
	 */
	public function getObjects ($classes, $query, $params = array()) {
		$result = $this->query($query, $params);
		$sofa = new ObjectBuilder($result);
		return $sofa->build($classes);
	}

	/**
	 * Prepares and returns the query by escaping and interpolating any parameters into the query.
	 *
	 * @param string $query The query to prepare.
	 * @param mixed $params Parameters to escape and interpolate into query.
	 * @return string		The query prepared to be executed.
	 */
	protected function prepareQuery ($query, $params) {
		if (is_object($params)) {
			/*
			 * Regexp to match placeholders according to the rules of PHP variables, and excluding
			 * double colons :: which indicates a cast in SQL and is thus not a placeholder.
			 * We also include the first character after the placeholder, 
			 */
			$allowedChars = '/[^:]:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)(\W?)/';
			$matches = array();
			preg_match_all($allowedChars, $query, $matches);

			if (empty($matches[0])) {
				throw new SqlException('Object parameter was supplied, but no named parameters
					were defined in the query.', $query);
			}

			/**
			 * Loop through placeholder matches and create search/replace strings for those that
			 * actually exists as parameters.
			 */
			foreach ($matches[0] as $idx => $match) {
				$propertyName = $matches[1][$idx];
				if (property_exists($params, $propertyName)) {
					$search[] = $match;
					// The first character in the regexp match should not be replaced, so we prepend it
					$replace[] = $match{0} . $this->escapeValue($params->$propertyName) . $matches[2][$idx];
				}
				else {
					throw new SqlException('No property in parameter object ' . get_class($params)
						. " matches the named parameter $match.", $query);
				}
			}

			// Do the actual string interpolation
			return str_replace($search, $replace, $query);
		}

		/*
		 * A single NULL parameter must be "manually" wrapped in an array, otherwise we cast
		 * $params to an array in order to wrap any scalar value passed as a single parameter
		 * in an array.
		 */
		if ($params === null) {
			$escapedParams = array('NULL');
		}
		else {
			$escapedParams = array_map(array($this, 'escapeValue'), (array) $params);
		}

		/*
		 * With $params as a simple array, we expect ?-placeholders. Convert them to %s in order
		 * to simply use vsprintf().
		 */
		$transformedQuery = str_replace('?', '%s', $query);
		$preparedQuery = @vsprintf($transformedQuery, $escapedParams);

		if (!$preparedQuery) {
			$numReplacements = substr_count($transformedQuery, '%s');
			$numParams = count($escapedParams);
			throw new SqlException("Number of replacement chars in query ($numReplacements) "
				. "and parameter values ($numParams) does not match.", $query);
		}
		return $preparedQuery;
	}

	/**
	 * Stricter type check on numbers used when escaping values. Solves one specific problem where the
	 * string value contains prefixing zeroes, which most likely means it shouldn't be treated as a
	 * number but a string of digits. Ie. telephone numbers, hexadecimal strings, postal codes etc.
	 *
	 * @param string $value The value to check.
	 * @return bool			<code>TRUE</code> if the value is a pure number.
	 */
	protected function isPureNumber ($value) {
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
