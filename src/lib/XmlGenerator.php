<?php
/**
 * Generates XML from PHP data structures. Everything is automatically wrapped in a root element, named
 * 'result' by default. After creating an instance of the XmlGenerator, you add all the data you want
 * through the append() method. Everything from objects to arrays and pure scalar values are supported.
 * The only PHP data type that is not supported, and probably won't be, is the abstract 'resource' data type.
 * Finally you call getXml() to get a string representation of the XML version of your data.
 */
class XmlGenerator {
	private $document;
	
	/**
	 * Creates a new XML generator. An XML document is immediately created with a root element, named
	 * 'result' by default.
	 *
	 * @param string $rootElement Name of the document's root element, defaults to 'result'.
	 */
	public function __construct ($rootElement = 'result') {
		$this->document	= new DOMDocument('1.0', 'utf-8');
		
		try {
			$root = $this->document->createElement($rootElement);
			$this->document->appendChild($root);
		}
		catch (Exception $e) {
			throw new Exception("Invalid name of XML document's root element: $rootElement");
		}
	}
	
	/**
	 * Adds more data to the XML document. If you specify a container name, the data will be wrapped
	 * in an XML element of that name. Otherwise the element name for the data will be inferred from
	 * the type of data. An object will get it's element name from it's class name. An array will be
	 * wrapped in a <data> element, and scalar values will be wrapped in elements named after the scalar
	 * type, ie. <boolean> or <number>.
	 *
	 * If the data is an object you can also choose to have it be collapsed, meaning it will be converted
	 * to an array of which each value will be directly added to the XML document as individual data items.
	 * The conversion to an array follows Kolibri's way of exposing object data, either through a custom
	 * expose() method or by PHPs object iteration.
	 *
	 * @param mixed $data       The data to add. Any PHP data type can be added except for the abstract
	 *                          resource data type.
	 * @param string $container An optional name of the containing element for the data value. Defaults
	 *                          to a name inferred from the data type.
	 * @param bool $collapse    Only relevant for object data. When <code>TRUE</code> the object will
	 *                          be flattened to a array of individual data items to be appended.
	 */
	public function append ($data, $container = null, $collapse = false) {
		/**
		 * Objects are always flattened, but will normally be wrapped in a containing element with
		 * the name of it's class (unless manually set to something else).
		 * If on the other hand the object should be collapsed completely it won't be wrapped,
		 * instead being appended as a normal array would - each exposed property as separate elements.
		 */
		if (is_object($data)) {
			if ($collapse === true) {
				// Invalidate any container element if the object should be collapsed
				$container = null;
			}
			else if ($container === null) {
				$container = get_class($data);
			}
			
			$data = $this->flattenObject($data);
		}

		// If there's no container element, use the root element
		if ($container === null) {
			$container = $this->document->documentElement;
		}

		// Since objects are flattened, we only need to build arrays and scalar values
		if (is_array($data)) {
			$resultNode = $this->buildArray($data, $container, true);
		}
		else {
			$resultNode = $this->buildAtomic($data, $container);
		}
		
		// When there's a separate container element we need to append it after it's created
		if ($resultNode !== null && $resultNode !== $container) {
			$this->document->documentElement->appendChild($resultNode);
		}
	}

	/**
	 * Returns the DOM tree as an XML string.
	 *
	 * @return string The DOM tree with data represented as an XML string.
	 */
	public function getXml () {
		return $this->document->saveXML();
	}
	
	/**
	 * Deprecated. XmlGenerator now builds the DOM tree incrementally, each time new data is added.
	 *
	 * @see getXml
	 * @deprecated
	 * @return string				An XML representation of the PHP data variables, as a string.
	 */
	public function build () {
		return $this->document->saveXML();
	}
	
	/**
	 * Builds an XML representation of a PHP array.
	 *
	 * @param array $array			The array to convert to a DOM fragment.
	 * @param string $container		Name of the containing XML element for the array elements.
	 * @param bool $keepIndices		Indicates whether to keep indices specified in the array, or
	 * 								if object types takes precedence.
	 * @return DOMNode				An XML node representing the array for appending in a DOM tree.
	 */
	private function buildArray ($array, $container = null, $keepIndices = false) {
		if ($container === null || is_string($container)) {
			$name = (empty($container) ? 'data' : $container);
			$container = $this->document->createElement($name);
		}
		
		foreach ($array as $key => $value) {
			// Skip empty elements.
			if (!is_object($value) && !is_array($value) && !is_bool($value)
				&& ($value === null || $value === '')) continue;

			// Set childs element name if key is non-numeric and indices are to be kept
			if ((!$keepIndices && is_object($value)) || is_numeric($key)) {
				$elementName = null;
			}
			else {
				$elementName = str_replace('::', '-', $key);
			}

			if (is_scalar($value)) {
				// Create a new child node from a scalar value: string, number or boolean
				$child = $this->buildAtomic($value, $elementName);
			}
			else if (is_object($value)) {
				// Create a new child node from an object
				$child = $this->buildObject($value, $elementName);
			}
			else if (is_array($value)) {
				// Create a new child node from an array
				$child = $this->buildArray($value, $elementName);
			}
			else {
				// Array item is of unsupported data type (i.e. resource or function)
				$child = null;
			}

			if ($child !== null) {
				$container->appendChild($child);
			}
		}

		return ($container->hasChildNodes() ? $container : null);
	}
	
	/**
	 * Builds an XML representation of a PHP object.
	 *
	 * @param object $object	The object to convert to a DOM fragment.
	 * @param string $container	Name of the containing XML element for the objects attributes.
	 * @return DOMNode			An XML node representing the object for appending in a DOM tree.
	 */
	private function buildObject ($object, $container = null) {
		// Default element name for the containing element is the objects class.
		if ($container === null) {
			$container = get_class($object);
		}

		// Flatten object into a simple array of data values
		$vars = $this->flattenObject($object);
		
		return (!empty($vars) ? $this->buildArray($vars, $container, true) : null);
	}
	
	private function flattenObject ($object) {
		if (method_exists($object, 'expose')) {
			// Object prefers to decide its own DOM structure
			return $object->expose();
		}
		
		// Fetch all of the object's attributes
		$objData = array();
		foreach ($object as $key => $value) {
			$objData[$key] = $value;
		}

		return $objData;
	}
	
	/**
	 * Builds an XML representation of an atomic PHP variable (number, boolean or string).
	 *
	 * @param mixed $value	A number, boolean or string.
	 * @param string $name	An optional name for the containing XML element, otherwise a generic
	 *						name is used to describe the content.
	 * @return DOMElement	An XML element representing the variable for appending in a DOM tree.
	 */
	private function buildAtomic ($value, $name = null) {
		if ($name === null) {
			if (is_numeric($value)) {
				$name = 'number';
			}
			else if (is_bool($value)) {
				$name = 'boolean';
			}
			else {
				$name = 'string';
			}
		}
		else {
			$name = str_replace('::', '-', $name);
		}

		$element = $this->document->createElement($name);

		if ($this->isXml($value)) {
			$child = $this->document->createCDATASection($value);
		}
		else {
			if (is_bool($value)) {
				$value = ($value ? 'true' : 'false');
			}

			$child = $this->document->createTextNode($value);
		}

		$element->appendChild($child);
		return $element;
	}

	/**
	 * Checks if a string is XML data. The default is to do a complete parse of the string to see that it is
	 * well formed as well. Otherwise it will just check to see if the string starts and ends with < and >
	 * respectively.
	 *
	 * @param string $value				The string value to check.
	 * @param boolean $checkWellFormed	If <code>TRUE</code> the string will also be tried
	 *									to parse as XML to see if it's also well formed. If
	 *									<code>FALSE</code> only a probability test is done to evaluate
	 *									if the string is XML. Default is <code>FALSE</code>.
	 * @return bool						<code>TRUE</code> if the string is XML data, <code>FALSE</code>
	 *									if not.
	 */
	private function isXml ($value, $checkWellFormed = false) {
		if (is_string($value) && substr($value, 0, 1) == '<' && substr($value, -1) == '>') {
			if ($checkWellFormed) {
				$parser = xml_parser_create('utf-8');
				$retval = xml_parse($parser, $value, true);
				xml_parser_free($parser);

				return ($retval == 0);
			}
			else return true;
		}

		return false;
	}
}
?>
