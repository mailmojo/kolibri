<?php
/**
 * This class represents the results from a database query. In addition to providing access to the
 * underlying result array, this class contains convenience methods for extracting objects from the
 * results, including support for one-to-many relationships. All data returned from the set is
 * converted to native PHP types.
 * 
 * @version 	$Id: ResultSet.php 1523 2008-07-09 23:32:14Z anders $
 */
class ResultSet {
	/**
	 * @var DatabaseConnection
	 */
	var $connection;
	
	/**
	 * The result rows as a two-dimentional array.
	 * @var array
	 */
	var $results;
	
	/**
	 * Current index position in $results.
	 * @var int
	 */
	var $index;
	
	/**
	 * Creates an instance of this class, and optionally populates it with the data specified by
	 * <code>$results</code>. Note that this data must be an array with rows (arrays), else this
	 * instance will not function properly.
	 * 
	 * @param DatabaseConnection $connection	The database connection creating this result set.
	 * @param array $results					Optional data to populate this result set with.
	 * @return ResultSet
	 */
	function ResultSet ($connection, $results = null) {
		$this->connection = $connection;
		$this->results = $results;
		$this->index = 0;
	}
	
	/**
	 * Adds a result row to this set. The result is expected to be a single associative array. The
	 * cursor position of this result set is not modified by this method.
	 * 
	 * @param array $result		A result row to add to this set.
	 */
	function add_result ($result) {
		$this->results[] = $result;
	}
	
	/**
	 * Populates this result set with the supplied data. The results overwrite any existing data,
	 * and sets the cursor position to the first element.
	 * 
	 * Note that the results is expected be an array with numerically indexed rows, which in turn is
	 * associative arrays. Any other format may break the functionality of this result set.
	 * 
	 * @param array $results	The data to populate this result set with.
	 */
	function put_results ($results) {
		$this->results = $results;
		$this->index = 0;
	}
	
	/**
	 * Returns the next row in this result set, or <code>FALSE</code> if the end of the set is
	 * reached.
	 * 
	 * @return array	The next row.
	 */
	function next () {
		return (isset($this->results[$this->index]) ? $this->results[$this->index++] : false);
	}
	
	/**
	 * Populates an object with values in this result set.
	 * 
	 * Join specifications may be given to specify additional object types to instantiate per row, which in
	 * turn will be set onto a property of the outer-most object specified by each join. This will in effect
	 * create a hierarchy of objects, with <code>&$object</code> as the single outermost object. For a
	 * detailed description of join specifications syntax, see <code>DatabaseConnection</code>.
	 * 
	 * Each object (the main object as well as any object defined by the join specifications) is populated
	 * by values from columns with the same name as the properties of the object. For instance will a
	 * username-column populate a <code>username</code>-property in an object. Be aware that all columns
	 * will attempt to populate every object. This means that if two objects share the same property name
	 * as a single column, that columns value will be set in both objects.
	 *
	 * @param object &$object		Main object to populate.
	 * @param mixed $join			Join specifications to create an object hierarchy.
	 * @param mixed $row			Row to populate main object with. Only used by recursion.
	 * @return bool		<code>TRUE</code> if the object has been populated by any values or joined objects,
	 * 					<code>FALSE</code> if not.
	 */
	function fetch_into ($object, $join = null, $row = null) {
		if ($row === null) {
			// If row is null we are not in a recursion, so fetch first row
			$row = $this->next();
			if ($row === false) return false;
			$is_outer = true;
		}
		else {
			// We are in a recursion (a join), which means we may set PK as array key. Make sure it's defined.
			if (!is_string($object->pk())) {
				trigger_error('No primary key defined for model of type ' . get_class($object)
						. '. HINT: Have your model defined the pk() method?', E_USER_ERROR);
			}
			$is_outer = false;
		}
		
		$is_populated = $this->populate_object($object, $row);
		
		if (is_array($join) && !empty($join)) {
			do {
				foreach ($join as $property => $inner_joins) { // Loop through join specifications
					if (!is_array($inner_joins)) {
						// Class name is a pure string
						$inner_obj = new $inner_joins();
						$inner_joins = null;
					}
					else {
						// Class name is in array, use first element as class name and rest as new joins
						$inner_obj = new $inner_joins[0]();
						$inner_joins = (count($inner_joins) > 1 ? array_slice($inner_joins, 1) : null);
					}
					
					/*
					 * Fetch object from current row with any joins it may have, and put object into
					 * the outer object.
					 */
					if ($this->fetch_into($inner_obj, $inner_joins, $row)) {
						if ($this->put_into_property($object, $inner_obj, $property, $inner_joins)) {
							$is_populated = true;
						}
					}
				}
			} while ($is_outer && $row = $this->next()); // Only loop in the outermost call
		}
		
		return $is_populated;
	}
	
	/**
	 * Returns an array with objects populated by values in this result set.
	 * 
	 * TODO: Utvid kommentar...
	 */
	function fetch_objects ($classes) {
		if (empty($classes)) return false;
		
		// Create a container to hold the objects as fetch_into() populates a single outer object
		$container = new Container();
		$join = array('dataset' => $classes);
		
		$this->fetch_into($container, $join);
		return $container->dataset;
	}
	
	/**
	 * Puts the inner object (B) into a specific property of the outer object (A).
	 * 
	 * If B is already present in A according to the <code>equals()</code>-method of B, the object is not
	 * put. Instead, the inner joins (if specified) of B will be traversed, and their values added onto
	 * the object equal to B already present in A.
	 * 
	 * If B is <em>not</em> already present in the specified property of A, but one or more other objects
	 * are, they are not replaced but rather put into an array on the property.
	 * 
	 * @param object &$outer_obj	Outer object.
	 * @param object &$inner_obj	Inner object to put into <code>$outer_obj->$property</code>.
	 * @param string $property		Property of the outer object to put inner object into.
	 * @param array $inner_joins	Join specifications of objects joined into the inner object. Only used
	 * 								in this method if the inner object is already present in outer object
	 * 								(see description).
	 * @return bool		<code>TRUE</code> if the inner object was successfully set in the outer object, or
	 *					<code>FALSE</code> if the property did not exist in outer object.
	 */
	function put_into_property ($outer_obj, $inner_obj, $property, $inner_joins = null) {
		if (isset($outer_obj->$property)) { // Property has already been set
			$pkVar = $inner_obj->pk();
			$inner_pk = $inner_obj->$pkVar;

			// Make sure primary key value is not empty, it is used as key in the array in the property
			if ($inner_pk === null || $inner_pk == '') {
				trigger_error("No primary key value in object of type " . get_class($inner_obj)
							. ". HINT: Primary key not selected in query?", E_USER_ERROR);
			}

			if (!is_array($outer_obj->$property)) {
				$propertyPkVar = $outer_obj->$property->pk();
				$propertyPk = $outer_obj->$property->$propertyPkVar;

				if ($propertyPk != $inner_pk) {
					/*
					 * Property is not an array and inner obj isn't yet added. Convert property to array
					 * and add it.
					 */
					$outer_obj->$property = array($propertyPk => $outer_obj->$property, $inner_pk => $inner_obj);
					return true;
				}
			}
			else if (is_array($outer_obj->$property) && !isset($outer_obj->{$property}[$inner_pk])) {
				// Property is an array and inner obj isn't yet added - add it
				$outer_obj->{$property}[$inner_pk] = $inner_obj;
				return true;
			}
			//else {
				/*
				 * Inner obj is already set in outer obj. If inner obj has any joins, it may *contain*
				 * unique data compared to the one already set in outer obj. We must make sure any such
				 * data is copied out to the inner obj already present in outer obj.
				 */
				if (!empty($inner_joins)) {
					foreach ($inner_joins as $inner_prop => $deeper_joins) {
						if (isset($inner_obj->$inner_prop)) { // Make sure inner property isn't null
							if (is_array($deeper_joins)) {
								/*
								 * Extract even deeper joins. We need to pass them on in order to recurse
								 * all the way to the deepest object. Then one property after another will
								 * be copied outwards.
								 */
								$deeper_joins = (count($deeper_joins) > 1
										? array_slice($deeper_joins, 1) : null);
							}
							else $deeper_joins = null;
							
							/*
							 * Make sure the inner property isn't empty (null or array without any 
							 * elements), else we get an error when trying to move it outwards.
							 */
							if (!empty($inner_obj->$inner_prop)) {
								/*
								 * The join property in inner obj will never contain more than one element (as
								 * it has been created in and by the currently processing row). However, it can
								 * still be inside an array, if the property is defined to always be an array in
								 * the model. Thus, if it is in an array, it must be extracted before being
								 * copied outwards.
								 */
								if (is_array($inner_obj->$inner_prop)) {
									$inner_obj->$inner_prop = array_pop($inner_obj->$inner_prop);
								}
								
								if (!is_array($outer_obj->$property)) { // Property in outer obj is single object
									$this->put_into_property($outer_obj->$property,
											$inner_obj->$inner_prop, $inner_prop, $deeper_joins);
								}
								else {
									// Property in outer obj is array and inner obj exists in outer obj already
									$this->put_into_property($outer_obj->{$property}[$inner_pk],
											$inner_obj->$inner_prop, $inner_prop, $deeper_joins);
								}
							}
						}
					}
				}
			//}
		}
		else { // Property is not yet set
			// Property must be a valid property in outer obj for inner obj to be set
			if (array_key_exists($property, get_object_vars($outer_obj))) {
				$outer_obj->$property = $inner_obj;
			}
			else return false;
		}
		return true;
	}
	
	/**
	 * Returns the results in this set in its native associative array format.
	 * 
	 * @return array	All result rows.
	 */
	function get_result_array () {
		return $this->results;
	}
	
	/**
	 * Populates an object with values from an associated array.
	 * 
	 * The object is populated by values from the array with the same keys as properties in the
	 * object. For instance, a key with the name <code>username</code> will only populate
	 * a <code>username</code> property in the object.
	 *
	 * @param object &$object	Object to populate.
	 * @param array $row		Associated array to populate object with.
	 * @return bool		<code>TRUE</code> if the object was populated with one or more values,
	 * 					<code>FALSE</code> if not.
	 */
	function populate_object ($object, $row) {
		/*
		 * We don't populate object when PK is empty, as we can then assume the object is "empty" for all
		 * we care.
		 */
		if (empty($row[$object->pk()])) {
			return false;
		}

		$properties = array_keys(get_object_vars($object));
		
		$is_populated = false;
		foreach ($row as $column => $value) {
			if ($value !== null && in_array($column, $properties)) {
				// Correct the data encoding and type as needed
				$object->$column = $this->convert_type($value);
				$is_populated = true;
			}
		}

		if ($is_populated) {
			// Set the original property so we know this object can be UPDATEd
			$object->original = $row[$object->pk()];
		}
		return $is_populated;
	}
	
	/**
	 * Converts a value retrieved from the database into a more meaningfull type (i.e. textual
	 * boolean values into true boolean values).
	 * 
	 * @param string $value		The value to check/convert the type of.
	 * @return mixed	The value converted.
	 */
	function convert_type ($value) {
		if ($value == 't' || $value == 'f') {
			return ($value == 't' ? true : false);
		}
		else if ($value == 'NULL') {
			return null;
		}
		return $value;
	}
}
?>
