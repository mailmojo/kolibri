<?php
require(ROOT . '/database/ObjectBuilder.php');

/**
 * This abstract class defines the methods used to communicate with a database, and the contract
 * specific database connection implementations must follow.
 * 
 * @version 	$Id: DatabaseConnection.php 1534 2008-08-01 14:18:52Z frode $
 */
abstract class DatabaseConnection {
	protected $connection;
	protected $resultSet;

	abstract public function __construct ($conf);

	abstract public function connect ();

	abstract public function begin ();

	abstract public function commit ();

	abstract public function rollback ();

	abstract public function query ($query, $params = null);

	abstract public function lastInsertId ();

	abstract protected function escapeValue ($value);

	public function getColumn ($query, $params = null, $column = null) {
		$result = $this->query($query, $params);
		if ($result && $result->valid()) {
			$row = $result->current();
			return !$column ? current($row) : $row[$column];
		}
		return false;
	}

	public function getObject ($classes, $query, $params = null) {
		if (is_array($classes)) {
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
			return $sofa->fetchInfo($object, $classes);
		}
		return false;
	}

	public function getObjects ($classes, $query, $params = null) {
		$result = $this->query($query, $params);
		if ($result) {
			$sofa = new ObjectBuilder($result);
			return $sofa->build($classes);
		}
		return false;
	}

	public function numAffectedRows () {
		return $this->resultSet->numAffectedRows();
	}

	protected function prepareQuery ($query, $params) {
		if (!empty($params)) {
			if (is_array($params)) {
				$escapedParams = array_map(array($this, 'escapeValue'), $params);
				$transformedQuery = str_replace('?', '%s', $query);
				$preparedQuery = vsprintf($transformedQuery, $escapedParams);

				if (!$preparedQuery) {
					throw new Exception('Number of replacement chars and parameter values does not match.');
				}
			}
			else if (is_object($params)) {
				$allowedChars = '/[^:]:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/';
				$matches = array();
				preg_match_all($allowedChars, $query, $matches);

				foreach ($matches[1] as $match) {
					if (property_exists($params, $match)) {
						$pattern[] = "/[^:]:$match/";
						$replace[] = $this->escapeValue($params->$match);
					}
					else {
						throw new Exception("No property in parameter object matches the named parameter $match.");
					}
				}

				return preg_replace($pattern, $replace, $query);
			}
		}

		return $query;
	}
}
