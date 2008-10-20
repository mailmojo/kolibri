<?php
require(ROOT . '/database/ResultSet.php');
require(ROOT . '/database/Container.php');

/**
 * This class handles all communication with a database. This is the base class which exposes
 * general functionality regardless of the actual database system in use. A subclass must be
 * used to communicate with a specific database system.
 * 
 * TODO: Major cleanup++
 * 
 * @version 	$Id: DatabaseConnection.php 1534 2008-08-01 14:18:52Z frode $
 */
abstract class DatabaseConnection {
	protected $connection;
	
	var $in_transaction;
	var $autocommit;
	var $aborted;

	/**
	 * The last result set retrieved by this connection.
	 * @var ResultSet
	 */ 
	var $result_set;
	
	/**
	 * The resource returned from the last query executed.
	 * @var resource
	 */
	var $resource;
	
	var $lastError;
	var $lastQuery;

	/** The query execution function to use. Must be specified by a subclass. */
	protected $query_func;
	/** The error retrieving function to use. Must be specified by a subclass. */
	protected $error_func;
	/** The function to use when fetching all rows from the resource. Must be specified by a subclass. */
	protected $fetch_all_func;
	/** The function to use when fetching a single row from the resource. Must be specified by a subclass. */
	protected $fetch_row_func;
	/** The function to use for . Must be specified by a subclass. */
	protected $affected_rows_func;
	
	private static $instance;
	
	/**
	 * Abstract function to connect to the database described in this object. Must be implemented
	 * in a subclass.
	 */
	public abstract function connect ();
	
	/**
	 * Executes a SQL statement and returns its result resource, or <code>FALSE</code> if the
	 * statement failed.
	 * 
	 * If an array is passed to the optional <code>$vars</code> parameter, each element is passed to
	 * an <code>escape_value</code> method, which takes care of escaping the values before they are
	 * interpolated into the query. Standard string interpolation (i.e. %s, %d) must be used by the
	 * query.
	 * 
	 * @param string $query		SQL statement to execute.
	 * @param array $vars		Data to interpolate into the query.
	 * @return mixed	The result resource or <code>FALSE</code> if any error occurs.
	 */
	function execute ($query, $vars = null) {
		// If we are not connected, connect
		if (!$this->connection) {
			if (!$this->connect()) return false;
		}

		// If transaction is aborted, do not execute query. Call rollback() to clear aborted state.
		if ($this->aborted) {
			return false;
		}

		// Begin new transaction if not in autocommit mode and not already in transaction
		if (!$this->is_autocommit() && !$this->is_in_transaction()) {
			$this->begin();
		}

		// Interpolate data into query
		if (!($preparedQuery = $this->prepare_query($query, $vars))) {
			$this->lastQuery = $query;
			$this->lastError = 'Number of replacement arguments and actual values does not match.';
			$this->aborted = true;
			return false;
		}

		$query_func = $this->query_func;
		$this->lastQuery = $preparedQuery;
		$this->resource = $query_func($this->connection, $preparedQuery);

		if (!$this->resource) {
			$this->aborted = true;
			$errorFunc = $this->error_func;
			$this->lastError = $errorFunc($this->connection);
		}

		return $this->resource;
	}
	
	/**
	 * @return bool	TRUE when the query was successfully run, FALSE otherwise.
	 */
	public function exec ($query, $vars = null) {
		return ($this->execute($query, $vars) !== false);
	}

	/**
	 * @return mixed	Numeric ID for the inserted row, FALSE if the insert failed.
	 */
	public function insert ($query, $vars = null) {
		if ($this->execute($query, $vars)) {
			return $this->lastInsertId();
		}
		return false;
	}
	
	/**
	 * Begins a transaction if a transaction is not already active.
	 */
	function begin () {
		if (!$this->is_in_transaction()) {
			if (!$this->connection) {
				$this->connect();
			}

			$query_func = $this->query_func;
			$query_func($this->connection, 'BEGIN');
			$this->in_transaction = true;
		}
	}

	/**
	 * Commits the current transaction, if active. Returns <code>TRUE</code> if the transaction was commited,
	 * <code>FALSE</code> if this connection is not within a active transaction.
	 *
	 * @return bool		<code>TRUE</code> if transaction was commited, <code>FALSE</code> if not.
	 */
	function commit () {
		if ($this->is_in_transaction()) {
			$query_func = $this->query_func;
			$this->in_transaction = false;
			$this->aborted = false;

			if (!$this->aborted) {
				$query_func($this->connection, 'COMMIT');
				return true;
			}

			$query_func($this->connection, 'ROLLBACK');
		}

		return false;
	}

	/**
	 * Rolls back the current transaction, if active. Returns <code>TRUE</code> if the transaction was rolled 
	 * back, <code>FALSE</code> if this connection is not within a active transaction.
	 *
	 * @return bool		<code>TRUE</code> if transaction was rolled back, <code>FALSE</code> if not.
	 */
	function rollback () {
		if ($this->is_in_transaction()) {
			$query_func = $this->query_func;
			$query_func($this->connection, 'ROLLBACK');

			$this->in_transaction = false;
			$this->aborted = false;
			return true;
		}

		return false;
	}

	/**
	 * Returns <code>TRUE</code> if this connection is within a transaction, <code>FALSE</code> if not.
	 *
	 * @return bool
	 */
	function is_in_transaction () {
		return $this->in_transaction;
	}

	/**
	 * Sets the autocommit mode on (<code>TRUE</code>, the default) or off (<code>FALSE</code>).
	 *
	 * When autocommit mode is OFF, and if this connection is not already within a transaction, a
	 * transaction will implicitly be started upon the first query execution. It is up to the user
	 * to commit the transaction by calling <code>commit()</code>.
	 *
	 * With autocommit mode ON, all queries are automatically executed within their own transactions.
	 *
	 * @param bool $autocommit	Set autocommit mode on?
	 */
	function set_autocommit ($autocommit = false) {
		$this->autocommit = $autocommit;
	}

	/**
	 * Returns <code>TRUE</code> if autocommit mode is on, <code>FALSE</code> if it is off.
	 *
	 * @return bool
	 */
	function is_autocommit () {
		return $this->autocommit;
	}

	function isAborted () {
		return $this->aborted;
	}

	function getLastError () {
		return $this->lastError;
	}

	function getLastQuery () {
		return $this->lastQuery;
	}

	/**
	 * Prepares the query by escaping any values and interpolating them into the query.
	 *
	 * @param string $query		The query to interpolate values into.
	 * @param array $vars		Values to prepare and interpolate into query.
	 * @return string			Query prepared to be used.
	 */
	function prepare_query ($query, $vars) {
		$prepared_query = $query;

		// Interpolate data into query if $vars contains data
		if (!empty($vars) && is_array($vars)) {
			$vars = array_map(array($this, 'escapeValue'), $vars);
			$prepared_query = vsprintf($query, $vars);
		}

		return $prepared_query;
	}

	/**
	 * Fetches the results from the supplied result resource and returns a <code>ResultSet</code>.
	 *
	 * @param resource $res		Result resource to fetch results from.
	 * @return ResultSet		Containing the results.
	 */
	function query_results ($res) {
		$fetch_all_func = $this->fetch_all_func;

		if (($results = $fetch_all_func($res)) === false) {
			$this->result_set = null;
			return false;
		}

		$this->result_set = new ResultSet($this, $results);
		return $this->result_set;
	}
	
	/**
	 * Executes a SQL statement and returns a <code>ResultSet</code> with the results. <code>FALSE</code>
	 * is returned if an error occured while executing the statement.
	 * 
	 * An array may be passed to the <code>$vars</code> parameter. Each element will be passed to an
	 * <code>escape_value</code> method, which takes care of escaping the values before they are
	 * interpolated into the query. Standard string interpolation (i.e. %s, %d) must be used by the
	 * query to match these variables.
	 * 
	 * @param string $query		SQL statement to execute.
	 * @param array $vars		Data to interpolate into the query.
	 * @return ResultSet	With the query results, or <code>FALSE</code> if an error occured.
	 */
	function query ($query, $vars = null) {
		$res = $this->execute($query, $vars);
		if ($res !== false) {
			return $this->query_results($res);
		}
		return false;
	}
	
	/**
	 * TODO: Denne fases ut... Wapper foreløpig get_objects for å slippe endre ørten klientkall.
	 * 
	 * Executes a SQL query and returns an array of objects populated by values from the query
	 * result.
	 * 
	 * The objects returned are of the type specified in the <code>$class</code> parameter, while
	 * <code>$vars</code> is an optional array of values to interpolate into the query.
	 * 
	 * The <code>$join</code> parameter is optional, and may be a two-dimensional array describing
	 * other objects to create and put into the main object per result row. This is useful with join
	 * queries, where the joined tables can populate other objects than the main object. The first
	 * element in the second dimension array is the name of the class to create, and the second element
	 * the name of a variable in the main object where the new object instance will be assigned.
	 * If the join is a one-to-many relationship, the variable in the main object is created as an
	 * array, and new objects are added to this array until a row descibes a new main object entity. 
	 * 
	 * @param string $class		Name of the class to create for each main object. 
	 * @param string $query		Query to execute.
	 * @param array $vars		Data to interpolate into the query.
	 * @param array $join		Optional array describing objects the main object are joined against.
	 * @return array	An array of objects describing all rows found, or <code>FALSE</code> if no
	 *					rows are found.
	 */
	function get_all ($class, $query, $vars = null, $join = null) {
		if (!empty($join)) {
			$class = array($class) + $join;
		}
		return $this->get_objects($class, $query, $vars);
	}
	
	/**
	 * Executes a SQL query and returns an array of objects populated by values from the query
	 * result.
	 * 
	 * TODO: Kommenter... Erstatter get_all.
	 */
	function getObjects ($classes, $query, $vars = null) {
		if ($this->query($query, $vars)) {
			return $this->result_set->fetch_objects($classes);
		}
		return array();
	}
	
	/**
	 * Executes a SQL query and returns a two-dimensional array with the values of the query. The array
	 * returned is in the native query result format, meaning that no hierarchy has been created as
	 * can be the case with get_objects().
	 * 
	 * @param string $query		Query to execute.
	 * @param array $vars		Data to interpolate into the query.
	 */
	function get_arrays ($query, $vars = null) {
		if ($this->query($query, $vars)) {
			return $this->result_set->get_result_array();
		}
		return array();
	}
	
	/**
	 * Executes a SQL query and populates the specified object with the first row from the query
	 * results.
	 * 
	 * <code>$vars</code> is an optional parameter with an array of values which will be
	 * interpolated into the query.
	 * 
	 * The <code>$join</code> parameter is also optional, and may be a two-dimensional array describing
	 * other objects to create and put into the main object per result row. This is useful with join
	 * queries, where the joined tables can populate other objects than the main object. The first
	 * element in the second dimension array is the name of the class to create, and the second element
	 * the name of a variable in the main object where the new object instance will be assigned.
	 * If the join is a one-to-many relationship, the variable in the main object is created as an
	 * array, and new objects are added to this array until a row descibes a new main object entity. 
	 * 
	 * @param object &$object	The object to populate.
	 * @param string $query		Query to execute.
	 * @param array $vars		Data to interpolate into the query.
	 * @param array $join		Optional array describing objects the main object are joined against.
	 * @return bool		<code>TRUE</code> if the object was populated, <code>FALSE</code> if not.
	 */
	function get_into ($object, $query, $vars = null, $join = null) {
		if ($this->query($query, $vars)) {
			return $this->result_set->fetch_into($object, $join);
		}
		return false;
	}
	
	/**
	 * Executes a SQL query and returns an object populated by values from the first row of the
	 * query result.
	 * 
	 * The object returned is of the type specified in the <code>$class</code> parameter, while
	 * <code>$vars</code> is an optional array of values to interpolate into the query.
	 * 
	 * The <code>$join</code> parameter is also optional, and may be a two-dimensional array describing
	 * other objects to create and put into the main object per result row. This is useful with join
	 * queries, where the joined tables can populate other objects than the main object. The first
	 * element in the second dimension array is the name of the class to create, and the second element
	 * the name of a variable in the main object where the new object instance will be assigned.
	 * If the join is a one-to-many relationship, the variable in the main object is created as an
	 * array, and new objects are added to this array until a row descibes a new main object entity. 
	 * 
	 * @param string $class		Name of the class to create and populate.
	 * @param string $query		Query to execute.
	 * @param array $vars		Data to interpolate into the query.
	 * @param array $join		Optional array describing objects the main object are joined against.
	 * @return mixed		An object populated with query results, or <code>FALSE</code> if no
	 * 					rows are found.
	 */
	function getObject ($classes, $query, $vars = null) {
		if (is_array($classes)) {
			$mainClass = array_shift($classes);
			$object = new $mainClass();
			$join = $classes;
		}
		else {
			$object = new $classes();
			$join = null;
		}
		
		if ($this->get_into($object, $query, $vars, $join)) {
			return $object;
		}
		return false;
	}
	
	/**
	 * Executes a SQL query and returns an object populated by values from the first row of the
	 * query result.
	 * 
	 * The object returned is of the type specified in the <code>$class</code> parameter, while
	 * <code>$vars</code> is an optional array of values to interpolate into the query.
	 * 
	 * The <code>$join</code> parameter is also optional, and may be a two-dimensional array describing
	 * other objects to create and put into the main object per result row. This is useful with join
	 * queries, where the joined tables can populate other objects than the main object. The first
	 * element in the second dimension array is the name of the class to create, and the second element
	 * the name of a variable in the main object where the new object instance will be assigned.
	 * If the join is a one-to-many relationship, the variable in the main object is created as an
	 * array, and new objects are added to this array until a row descibes a new main object entity. 
	 * 
	 * @param string $class		Name of the class to create and populate.
	 * @param string $query		Query to execute.
	 * @param array $vars		Data to interpolate into the query.
	 * @param array $join		Optional array describing objects the main object are joined against.
	 * @return mixed		An object populated with query results, or <code>FALSE</code> if no
	 * 					rows are found.
	 */
	function getOne ($query, $vars = null) {
		if ($this->query($query, $vars)) {
			$row = $this->result_set->next();
			return (!empty($row) ? $this->result_set->convert_type(current($row)) : false);
		}
		return false;
	}
	
	/**
	 * Executes a SQL query and returns the first row of the query result as an associative array.
	 * 
	 * <code>$vars</code> is an optional parameter with an array of values which will be
	 * interpolated into the query.
	 * 
	 * @param string $query		Query to execute.
	 * @param array $vars		Data to interpolate into the query.
	 * @return array	The first row of the query result as an associative array, or <code>FALSE</code>
	 * 					if no rows are found.
	 */
	function get_row ($query, $vars = null) {
		$res = $this->execute($query, $vars);
		$fetch_all_func = $this->fetch_all_func;
		
		if (!$res || !($results = $fetch_all_func($res))) {
			return false;
		}
		
		$this->result_set = new ResultSet($this, $results);
		return $this->result_set->next();
	}
	
	/**
	 * Returns the number of rows actually affected by the previous INSERT, UPDATE or DELETE
	 * performed by this connection.
	 *
	 * @return int	Number of affected rows.
	 */
	public function numAffectedRows () {
		$affected_rows_func = $this->affected_rows_func;
		return $affected_rows_func($this->resource);
	}
	
	/**
	 * Abstract method to escape values which are to be used in a SQL query. A concrete implementation
	 * must take care to escape the values according to the database system the class communicates with,
	 * and return a value which is safe to use in a query string.
	 * 
	 * @param mixed $value	The value to escape.
	 * @return string	The value escaped according to a specific database system.
	 */ 
	public abstract function escapeValue ($value);
}
?>
