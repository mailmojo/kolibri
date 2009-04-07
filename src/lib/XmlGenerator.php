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
	 * Main entry point for building the XML structure out of PHP data structures. Distinguishes between
	 * PHP data types as either scalar or complex and calls appropriate methods for building the XML.
	 *
	 * @return DOMElement	A single DOMElement containing all non-NULL data in the data supplied, or NULL
	 *						if the build resulted in an empty element.
	 */
	private function build ($data, $container) {
		// "Short circuit" if the data is scalar
		if (is_scalar($data)) {
			return $this->buildScalar($data, $container);
		}
		
		// Prepare the containing element
		if (is_string($container)) {
			$container = $this->createElement($container);
		}
		
		$dataIsObject = is_object($data);
		
		// Extract data from a proxy explicitly
		if ($dataIsObject && ($data instanceof Proxy)) {
			$data = $data->extract();
			// Re-evaluate type of data, extract() can return an array
			$dataIsObject = is_object($data);
		}
		
		$this->buildComplex($data, $container, $dataIsObject);
		
		return ($container->hasChildNodes() ? $container : null);
	}

	/**
	 * Builds arrays and objects, known as complex data types.
	 */
	private function buildComplex ($data, $container, $isObject = false) {
		// Iterate through the array values or object properties
		foreach ($data as $key => $value) {
			// Skip NULL values and empty strings
			if ($value === null || $value === '') continue;
			
			// Use property name as key when building an object
			if ($isObject) {
				$elementName = $this->normalizeElementName($key);
			}
			// Use class name as element name for objects in an array
			else if (is_object($value)) {
				$elementName = get_class($value);
			}
			// Try to use array key if it is a string (not numeric)
			else if (is_string($key)) {
				$elementName = $key;
			}
			// Data is an array and we've found no fitting element name, use default
			else {
				$elementName = 'array';
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
	 * Wrapper for creating a DOM element and catching any exceptions for invalid characters in
	 * element name etc.
	 *
	 * @throws Exception	Invalid characters in element name will throw exception.
	 * @param string $name	The name of the element to create.
	 * @return DOMElement	The created DOMElement.
	 */
	private function createElement ($name) {
		try {
			$element = $this->document->createElement($name);
		}
		catch (DOMException $e) {
			throw new Exception("Invalid character in XML element name: $name");
		}
		
		return $element;
	}
	
	/**
	 * Extremely simple normalization for now. Since we support :: in request parameter names for
	 * indicating object hierarchy we need to replace this with a valid XML element name character.
	 *
	 * @param string $name	Name to normalize.
	 * @return string	The normalized name.
	 */
	private function normalizeElementName ($name) {
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
