<?php
/**
 * Generates XML from PHP data structures.
 * 
 * @version		$Id: XmlGenerator.php 1552 2008-09-19 12:49:04Z frode $
 */
class XmlGenerator {
	private $document;
	private $data;
	
	/**
	 * Creates a new XML generator and creates a new DOM tree with the correct
	 * DOM interface for the running PHP version.
	 *
	 * @param mixed $data	Optional data to generate XML from. See XmlGenerator::append().
	 */
	public function __construct ($data = null) {
		$this->document	= new DOMDocument('1.0', 'utf-8');
		$this->data		= array();
		
		if ($data !== null) {
			$this->append($data);
		}
	}
	
	/**
	 * Appends data to the list of data for which the generator should build XML from.
	 *
	 * @param mixed $data		The new data to append. If index is specified, the data is stored
	 *							under the same index internally to represent a specific child element
	 *							name. Otherwise, if the data is an array, it is merged with the
	 *							internal data array, if not it is appended sequentially.
	 *							The internal data array represents the children of the XML document's
	 *							root element.
	 * @param string $index		An optional index to store the data in, which will be used as the
	 *							element name for the data's containing element in the XML tree.
	 * @param bool $collapse	<code>TRUE</code> if the <code>data</code> should be flattened if it
	 *							is an object, <code>FALSE</<code> if not.
	 */
	public function append ($data, $index = null, $collapse = false) {
		if ($collapse) {
			if (is_object($data) && $data instanceof Exposable) {
				if (method_exists($data, 'expose')) {
					$data = $data->expose();
				}
				else {
					$data = get_object_vars($data);
				}
			}
		}

		if ($index !== null) {
			$this->data[$index] = $data;
		}
		else {
			if (is_array($data)) {
				$this->data = array_merge($this->data, $data);
			}
			else {
				$this->data[] = $data;
			}
		}
	}

	/**
	 * Builds the DOM tree and returns an XML representation of the data.
	 *
	 * @param mixed $data			The PHP data variables to generate XML from.
	 * @param string $rootElement	The name of the generated documents root element.
	 *								If not supplied, the generator will make an educated guess
	 *								from the data variables, or use a default name.
	 * @return string				An XML representation of the PHP data variables, as a string.
	 */
	public function build ($rootElement = 'result') {
		$root = $this->buildArray($this->data, $rootElement, true);
		$this->document->appendChild($root);

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
		if ($container === null) {
			$container = 'data';
		}

		$node = $this->document->createElement($container);//strtolower($container));

		foreach ($array as $key => $value) {
			// Skip empty elements.
			if (!is_object($value) && !is_array($value) && !is_bool($value)
				&& ($value === null || $value === '')) continue;

			// Set childs element name if key is non-numeric and indices are to be kept
			if ((!$keepIndices && is_object($value)) || is_numeric($key)) {
				$elementName = null;
			}
			else {
				$elementName = str_replace('::', '-', $key);//strtolower($key);
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

			if (!empty($child))	{
				$node->appendChild($child);
			}
		}

		return ($node->hasChildNodes() ? $node : null);
	}
	
	/**
	 * Builds an XML representation of a PHP object.
	 *
	 * @param object $object	The object to convert to a DOM fragment.
	 * @param string $container	Name of the containing XML element for the objects attributes.
	 * @return DOMNode			An XML node representing the object for appending in a DOM tree.
	 */
	private function buildObject ($object, $container = null) {
		if (!$object instanceof Exposable) {
			return null;
		}

		// Default element name for the containing element is the objects class.
		if ($container === null) {
			$container = get_class($object);
		}

		if (method_exists($object, 'expose')) {
			// Object prefers to decide its own DOM structure
			$vars = $object->expose();
		}
		else {
			// Fetch all of the object's attributes
			$vars = get_object_vars($object);
		}

		// Now we have a simple array to build
		return $this->buildArray($vars, $container, true);
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
			$name = str_replace('::', '-', $name);//$name;//strtolower($name);
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
