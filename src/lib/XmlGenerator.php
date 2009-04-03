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
		catch (DOMException $e) {
			throw new Exception("Invalid name of XML document's root element: $rootElement");
		}
	}
	
	public function getDom () {
		return $this->document;
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
	 * Adds more data to the XML document. If you specify a container name, the data will be wrapped
	 * in an XML element of that name. Otherwise the element name for the data will be inferred from
	 * the type of data. An object will get it's element name from it's class name. An array will be
	 * wrapped in an <array> element, and scalar values will be wrapped in elements named after the scalar
	 * type, ie. <boolean> or <number>.
	 *
	 * @param mixed $data       The data to add. Any PHP data type can be added except for the abstract
	 *                          resource data type.
	 * @param string $container An optional name of the containing element for the data value. Defaults
	 *                          to a name inferred from the data type.
	 */
	public function append ($data, $container = null) {
		// If no container element name is specified, append directly to the root element
		if ($container === null) {
			$container = $this->document->documentElement;
		}
		
		$resultNode = $this->build($data, $container);
		
		// When a container element is specified we need to append it to the root element after it's created
		if ($resultNode !== null && $resultNode !== $container) {
			$this->document->documentElement->appendChild($resultNode);
		}
	}

	private function build ($data, $container = null) {
		// "Short circuit" if the data is scalar
		if (is_scalar($data)) {
			return $this->buildScalar($data, $container);
		}
		
		// Prepare the containing element
		if (is_string($container)) {
			$container = $this->createElement($container);
		}
		else if ($container === null || !($container instanceof DOMNode)) {
			$container = $this->createElement('data');
		}
		
		$dataIsObject = is_object($data);
		
		// Let a data object control what is exposed if it wants to
		if ($dataIsObject && ($data instanceof Proxy)) {
			$data = $data->extract();
			// Re-evaluate type of data, extract returns array or object
			$dataIsObject = is_object($data);
		}
		
		$this->buildComplex($data, $container, $dataIsObject);
		
		// We only return non-empty elements
		return ($container->hasChildNodes() ? $container : null);
	}
	
	private function buildComplex ($data, $container, $isObject = false) {
		// Iterate through the array values or object properties
		foreach ($data as $key => $value) {
			// Skip NULL values and empty strings
			if ($value === null || $value === '') continue;
			
			// Use array index or object property name as element name unless it's a numeric value
			if ($isObject) {
				$elementName = $this->normalizeElementName($key);
			}
			// Use class name as element name for objects
			else if (is_object($value)) {
				$elementName = get_class($value);
			}
			else if (is_string($key)) {
				$elementName = $key;
			}
			else {
				$elementName = null;
			}

			$element = $this->build($value, $elementName);
			
			if ($element !== null) {
				$container->appendChild($element);
			}
		}
	}
	
	/**
	 * Builds an XML representation of an atomic PHP variable (number, boolean or string).
	 *
	 * @param mixed $value A number, boolean or string.
	 * @param string $name An optional name for the containing XML element, otherwise a generic
	 *                     name is used to describe the content.
	 * @return DOMElement  An XML element representing the variable for appending in a DOM tree.
	 */
	private function buildScalar ($value, $name = null) {
		if (is_string($name)) {
			$name = $this->normalizeElementName($name);
		}
		else if (is_numeric($value)) {
			$name = 'number';
		}
		else if (is_bool($value)) {
			$name = 'boolean';
		}
		else {
			$name = 'string';
		}

		/**
		 * Perform a simple test on string values to see if they're likely to be XML data.
		 * If so we wrap in a CDATA section to prevent < and > from being escaped, keeping the
		 * XML data intact.
		 */
		if (is_string($value) && $this->isXml($value)) {
			$child = $this->document->createCDATASection($value);
		}
		else {
			// We convert booleans to text values here, so they won't be subject to isXml tests
			if (is_bool($value)) {
				$value = ($value ? 'true' : 'false');
			}

			$child = $this->document->createTextNode($value);
		}
		
		$element = $this->createElement($name);
		$element->appendChild($child);
		return $element;
	}
	
	/**
	 * Wrapper for creating a DOM element and catching any exceptions.
	 */
	private function createElement ($name) {
		$element = null;
		
		try {
			$element = $this->document->createElement($name);
		}
		catch (DOMException $e) {
			if ($e->code == DOM_INVALID_CHARACTER_ERR) {
				throw new Exception("Invalid character in XML element name: $name");
			}
		}
		
		return $element;
	}
	
	private function normalizeElementName ($name) {
		/**
		 * We support :: in request parameter names indicating hierarchy of values.
		 */
		return str_replace('::', '-', $name);
	}

	/**
	 * Checks if a string is likely to be XML data. It simply checks to see if the string starts
	 * and ends with < and > respectively.
	 *
	 * @param string $value The string value to check.
	 * @return bool         <code>TRUE</code> if the string is XML data, <code>FALSE</code> if not.
	 */
	private function isXml ($value) {
		$value = trim($value);
		return (substr($value, 0, 1) == '<' && substr($value, -1) == '>');
	}
}
?>
