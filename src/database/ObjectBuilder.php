<?php
/**
 * This class builds objects with values from a database result set. It can build objects of a
 * single type simply returned in a flat array, to complex nested object structures. The type of
 * objects and structure to build is specified by the client when calling the <code>build()</code>
 * method, or more commonly when calling <code>getObject</code> or <code>getObjects</code> in a
 * <code>DatabaseConnection</code> object.
 *
 * When specifying the object types to build, a simple string is sufficent when no object nesting
 * is wanted (i.e. simply 'User' to get a list of User objects). However, when requesting object
 * nesting, a more complex array structure is required. Say you have a query to retrieve users with
 * their orders and products within. A related object structure could be specified like the
 * following:
 *
 *   array('User', 'orders' => array('Order', 'products' => 'Product));
 *
 * Here, the array returned from <code>build()</code> will contain User objects. The User objects
 * has a 'orders'-property which may contain Order objects which in turn has a 'products'-property
 * to hold Product objects. Whether or not orders and/or products will actually contain objects
 * depends on the query issued and its results.
 *
 * @version 	$Id: ResultSet.php 1523 2008-07-09 23:32:14Z anders $
 */
class ObjectBuilder {
	/**
	 * The result set we are building objects from.
	 * @var ResultSet
	 */
	private $resultSet;

	/**
	 * Caches primary keys for objects as they are encountered. Saves using reflection every time we
	 * need to look up the primary key of an object.
	 * @var array
	 */
	private $primaryKeys;
	
	/**
	 * Creates an instance of this class using the supplied object as our result set.
	 * 
	 * @param ResultSet $resultSet Result set to build objects from.
	 * @return ObjectBuilder
	 */
	public function __construct ($resultSet) {
		$this->resultSet   = $resultSet;
		$this->primaryKeys = array();
	}

	/**
	 * Builds a tree objects accoring to the class specifications supplied with values from the
	 * current result set. An array of the objects is returned. If the result set is empty, an empty
	 * array is returned.
	 *
	 * @param mixed $classes Object types to build. See class documentation for examples.
	 * @return array         Array of objects built and populated.
	 */
	public function build ($classes) {
		if (empty($classes)) {
			throw new Exception('No class names to build objects of were supplied');
		}
		if (!$this->resultSet->valid()) {
			return array();
		}

		// Create a container to hold the objects, as fetchInto() requires a single outer object
		$container = new Container();
		$join = array('dataset' => $classes);

		$this->fetchInto($container, $join);
		return $container->dataset;
	}

	/**
	 * Populates an object with values in this result set.
	 * 
	 * Each object (the main object as well as any inner objects defined by the class nesting
	 * specifications) is attempted populated by all values from each row in the result set. Column
	 * names must match the object properties they are to be set in. If an object's primary key
	 * property is not populated, the object is considered empty and we do not keep it.
	 *
	 * @param object $object    Main object to populate.
	 * @param mixed $classes    Specifies the hierarchy of objects to create.
	 * @param bool $isRecursing Specifies whether we are in a recursion or not. Used internally.
	 * @return bool             <code>TRUE</code> if the object has been populated by any values,
	 *                          <code>FALSE</code> if not.
	 */
	public function fetchInto ($object, $classes = null, $isRecursing = false, $row = null) {
		if ($row === null) {
			$row = $this->resultSet->current();
		}

		$isPopulated = $this->populateObject($object, $row);

		if (is_array($classes) && !empty($classes)) {
			do {
				foreach ($classes as $property => $innerClasses) { // Loop through class specifications
					/*
					 * Instantiate the current class to attempt to populate in our next recursive call.
					 * XXX: We might consider postponing instantiation until after we know PK is present.
					 * PK-check is currently done in populateObject() which requires an instance. This would
					 * probably speed up some cases with left joins with no/few matches, but requires some
					 * refactoring to be made clean.
					 */
					if (!is_array($innerClasses)) {
						$innerObj = new $innerClasses();
						$innerClasses = null;
					}
					else {
						$class = array_shift($innerClasses);
						$innerObj = new $class();
					}

					/*
					 * Fetch object from current row with any inner objects it may have, and put object into
					 * the outer object.
					 */
					if ($this->fetchInto($innerObj, $innerClasses, true, $row)) {
						if ($this->putIntoProperty($object, $innerObj, $property, $innerClasses)) {
							$isPopulated = true;
						}
					}
				}

				// We only loop and move the result set forward when not in a recursive execution
				if (!$isRecursing) {
					$this->resultSet->next(); // Move the result set row position forward
					if (!$this->resultSet->valid()) {
						break;
					}
					$row = $this->resultSet->current();
				}
			} while (!$isRecursing);
		}
		
		return $isPopulated;
	}
	
	/**
	 * Puts the inner object (B) into a specific property of the outer object (A).
	 * 
	 * If B is already present in A (according to primary key values), the object is not put.
	 * Instead, the inner classes of B (if specified) will be traversed, and their values added onto
	 * the object equal to B already present in A.
	 * 
	 * If B is <em>not</em> already present in the specified property of A, but one or more other
	 * objects are, they are not replaced but rather put into an array on the property.
	 * 
	 * @param object $outerObj    Outer object.
	 * @param object $innerObj    Inner object to put into <code>$outerObj</code>.
	 * @param string $property    Property of the <code>outerObj</code> to put <code>$innerObj</code>
	 *                            into.
	 * @param array $innerClasses Class specifications of objects inside <code>$innerObj</code>. Only
	 *                            used in this method if <code>$innerObj</code> is already present in
	 *                            <code>$outerObj</code>.
	 * @return bool               <code>TRUE</code> if <code>$innerObj</code> was successfully set in
	 *                            <code>$outerObj</code>, or <code>FALSE</code> if the property did
	 *                            not exist in <code>$outerObj</code>.
	 */
	private function putIntoProperty ($outerObj, $innerObj, $property, $innerClasses = null) {
		/*
		 * First we just make sure "something" is already set on the property, otherwise we can simply
		 * set the inner object without any special consideration.
		 */
		if (isset($outerObj->$property)) {
			$pkVar = $this->primaryKeys[get_class($innerObj)];
			$innerPk = $innerObj->$pkVar;

			if (!is_array($outerObj->$property)) {
				$propertyPkVar = $this->primaryKeys[get_class($outerObj->$property)];
				$propertyPk = $outerObj->$property->$propertyPkVar;

				if ($propertyPk != $innerPk) {
					/*
					 * Property is not an array and inner obj isn't yet added. Convert property to array
					 * and add it.
					 */
					$outerObj->$property = array($propertyPk => $outerObj->$property, $innerPk => $innerObj);
					return true;
				}
			}
			else if (is_array($outerObj->$property) && !isset($outerObj->{$property}[$innerPk])) {
				// Property is an array but inner obj isn't yet added - add it
				$outerObj->{$property}[$innerPk] = $innerObj;
				return true;
			}

			/*
			 * Inner obj is already set in outer obj. But if inner obj has inner classes, it may *contain*
			 * unique data compared to the one already set in outer obj. We must make sure any such
			 * data is copied out to the inner obj already present in outer obj.
			 */
			if (!empty($innerClasses)) {
				foreach ($innerClasses as $innerProp => $deeperClasses) {
					if (isset($innerObj->$innerProp)) { // No point copying if null
						if (is_array($deeperClasses)) {
							/*
							 * Extract even deeper classes. We need to pass them on in order to recurse all the way
							 * to the leaf object. Then one property after another will be copied outwards.
							 */
							$deeperClasses = count($deeperClasses) > 1 ? array_slice($deeperClasses, 1) : null;
						}
						else {
							$deeperClasses = null;
						}

						/*
						 * Make sure the inner property isn't empty (null or array without any elements), else we
						 * get an error when trying to move it outwards.
						 * XXX: We already know it's not null (isset()-check above), so should we count() instead?
						 */
						if (!empty($innerObj->$innerProp)) {
							/*
							 * The join property in inner obj will never contain more than one element (as it has been
							 * created in and by the currently processing row). However, it can still be inside an
							 * array, if the property is defined to always be an array in the model. Thus, if it is in
							 * an array, it must be extracted before being copied outwards.
							 */
							if (is_array($innerObj->$innerProp)) {
								$innerObj->$innerProp = array_pop($innerObj->$innerProp);
							}

							if (!is_array($outerObj->$property)) {
								// Property in outer obj is single object
								$this->putIntoProperty($outerObj->$property, $innerObj->$innerProp,
										$innerProp, $deeperClasses);
							}
							else {
								// Property in outer obj is array
								$this->putIntoProperty($outerObj->{$property}[$innerPk], $innerObj->$innerProp,
										$innerProp, $deeperClasses);
							}
						}
					}
				}
			}
		}
		else {
			// Nothing is yet set, so if the property exists we can simply set the inner object 
			if (property_exists($outerObj, $property)) {
				$outerObj->$property = $innerObj;
			}
			else {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Populates an object with values from a row from the result set (an associated array), and returns
	 * a boolean indicating whether the object was populated ornot.
	 * 
	 * The object is populated by values from the row with the same column names as properties in the
	 * object, converted to native PHP types as determined by the result set implementation.
	 *
	 * @param object $object Object to populate.
	 * @param array $row     Associated array to populate object with.
	 * @return bool          <code>TRUE</code> if the object was populated with one or more values,
	 *                       <code>FALSE</code> if not.
	 */
	private function populateObject ($object, $row) {
		$objClass = get_class($object);

		// If we have not yet cached the primary key of this object type, we do so here
		if (!isset($this->primaryKeys[$objClass])) {
			$reflection = new ReflectionObject($object);
			if (!$reflection->hasConstant('PK')) {
				throw new Exception('No primary key defined for model of type ' . $objClass
					. '. HINT: You must define a PK constant.');
			}

			$this->primaryKeys[$objClass] = $reflection->getConstant('PK');
		}

		// Don't populate object when PK is empty, as we use PK to compare objects
		if (!isset($row[$this->primaryKeys[$objClass]])) {
			return false;
		}

		$isPopulated = false;

		foreach ($row as $column => $value) {
			if ($value !== null && property_exists($object, $column)) {
				// Convert the data type as needed
				$object->$column = $this->resultSet->convertType($value);
				$isPopulated = true;
			}
		}

		if ($isPopulated) {
			// Set the "original"-property to PK value so we know this object can be UPDATEd
			$object->original = $row[$this->primaryKeys[$objClass]];
		}

		return $isPopulated;
	}
}

/**
 * This "private" class is a simple container to temporarily hold objects internally in
 * <code>ObjectBuilder</code>.
 */
class Container {
	public $dataset = array();
	const PK = null;
}
?>
