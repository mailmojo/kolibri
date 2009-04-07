<?php
/**
 * Generates XML from PHP data structures. Everything is automatically wrapped in a root element, named
 * 'result' by default. After creating an instance of the XmlGenerator, you add all the data you want
 * through the append() method. Everything from objects to arrays and pure scalar values are supported.
 * The only PHP data type that is not supported, and probably won't be, is the abstract 'resource' data type.
 * Finally you call getXml() to get a string representation of the XML version of your data, or getDom()
 * to get the DOMDocument directly (useful for utilizing the result with other XML technologies like XSLT).
 */
class XmlGenerator {
	// Default element names for scalar and complex data
	const SCALAR_ELEMENT_NAME = 'value';
	const COMPLEX_ELEMENT_NAME = 'data';
	
	private $document;
	
	/**
	 * Creates a new XML generator. An XML document is immediately created with a root element.
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
			throw new Exception("Invalid name for the XML document's root element: $rootElement");
		}
	}
	
	/**
	 * @return DOMDocument The DOMDocument for the generated XML structure.
	 */
	public function getDom () {
		return $this->document;
	}
	
	/**
	 * @return string The generated XML as a string.
	 */
	public function getXml () {
		return $this->document->saveXML();
	}
	
	/**
	 * Adds more data to the XML document. If you specify a container name, the data will be wrapped
	 * in an XML element of that name, otherwise the data will be appended directly to the root
	 * element. Complex data - arrays and objects - will have all their values/properties built separately
	 * and appended directly. Scalar data will be wrapped in a <value> element.
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
		else if (!is_string($container)) {
			throw new Exception('Containing element name must be a string.');
		}
		
		$resultNode = $this->build($data, $container);
		
		// When a container element is specified we need to append it to the root element after it's created
		if ($resultNode !== null && $resultNode !== $container) {
			$this->document->documentElement->appendChild($resultNode);
		}
	}

	/**
	 * Main entry point and routing method for building PHP data structures. The container
	 * for the data must be either an existing DOMNode (ie. the document element), a string
	 * with the name of an element or NULL for which a default name will be used. A new
	 * DOMElement will be created when the container is a string value or NULL.
	 *
	 * @param mixed $data		PHP data structure to build DOMNodes for (object, array or scalar data).
	 * @param mixed $container	An existing DOMNode, a string with an element name or NULL for a default
	 *							element name. Default element names are 'data' for complex data and
	 *							'value' for scalar values.
	 * @return DOMNode	A single DOMNode containing all non-NULL data supplied, or NULL
	 *					if the build resulted in an empty element. If a DOMNode was supplied as
	 *					the container this same DOMNode will be returned (with data appended).
	 */
	private function build ($data, $container) {
		// Prepare the containing element if necessary
		if (!$container instanceof DOMNode) {
			$container = $this->createElement($container, is_scalar($data));
		}

		if (is_scalar($data)) {
			$element = $this->buildScalar($data, $container);
		}
		else {
			// Extract data from a proxy explicitly
			if ($data instanceof Proxy) {
				$data = $data->extract();
			}

			$element = $this->buildComplex($data, $container);
		}
		
		return $element;
	}

	/**
	 * Builds arrays and objects, known as complex data types.
	 */
	private function buildComplex ($data, $container) {
		$dataIsObject = is_object($data);
		
		// Iterate through the array values or object properties
		foreach ($data as $key => $value) {
			// Skip NULL values and empty strings
			if ($value === null || $value === '') continue;

			$elementName = null;
			
			// Use property name as key when building an object, and array key when value is not an object
			if ($dataIsObject
					|| (is_string($key) && !is_object($value))) {
				$elementName = $key;
			}
			// Use class name as element name for objects in an array
			else if (is_object($value)) {
				$elementName = get_class($value);
			}

			$element = $this->build($value, $elementName);
			
			if ($element !== null) {
				$container->appendChild($element);
			}
		}
		
		return ($container->hasChildNodes() ? $container : null);
	}
	
	/**
	 * Builds an XML representation of a scalar variable (number, boolean or string).
	 *
	 * @param mixed $value A number, boolean or string.
	 * @param DOMNode $container	The DOMNode
	 * @return DOMElement  An XML element representing the variable for appending in a DOM tree.
	 */
	private function buildScalar ($value, $container) {
		// Wrap values likely to be XML in a CDATA section to prevent escaping of <, > and &
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
		
		$container->appendChild($child);
		return $container;
	}
	
	/**
	 * Wrapper for creating a DOM element and catching any exceptions for invalid characters in
	 * element name etc.
	 *
	 * @throws Exception		Invalid characters in element name will throw exception.
	 * @param string $name		The name of the element to create, or <code>NULL</code> for a default name.
	 * @param bool $forScalar	<code>TRUE</code> if the element will be used for a scalar value,
	 *							<code>FALSE</code> if it will contain several other elements.
	 *							This affects the element name chosen when none is supplied.
	 * @return DOMElement	The created DOMElement.
	 */
	private function createElement ($name, $forScalar) {
		if (!is_string($name)) {
			$name = ($forScalar ? self::SCALAR_ELEMENT_NAME : self::COMPLEX_ELEMENT_NAME);
		}
		
		try {
			$element = $this->document->createElement($name);
		}
		catch (DOMException $e) {
			throw new Exception("Invalid character in XML element name: $name");
		}
		
		return $element;
	}
	
	/**
	 * Checks if a string is likely to be XML data. It simply checks to see if the string starts
	 * and ends with < and > respectively.
	 *
	 * @param string $value The string value to check.
	 * @return bool	<code>TRUE</code> if the string is XML data, <code>FALSE</code> if not.
	 */
	private function isXml ($value) {
		return (substr($value, 0, 1) == '<' && substr($value, -1) == '>');
	}
}
?>
