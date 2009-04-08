<?php
/**
 * This class provides an abstraction of the XSL transformation capabilities in PHP 5.
 */
class XslTransformer {
	private $stylesheet;
	private $xmlData;
	private $parameters;

	/**
	 * Creates a new XSL transformer with a stylesheet to transform data with.
	 *
	 * @param string $xsl	Path to stylesheet.
	 * @return XslTransformer
	 */
	public function __construct ($xsl) {
		$this->stylesheet = new DOMDocument();
		$this->stylesheet->load($xsl);
	}

	/**
	 * Adds XML data from a string or a file.
	 *
	 * @param string $xml  XML data or path to XML file.
	 * @param bool $isFile Is $xml a file name? Defaults to <code>FALSE</code>.
	 */
	public function addXml ($xml, $isFile = false) {
		if ($isFile) {
			if (file_exists($xml)) {
				$xml = file_get_contents($xml);
			}
			else {
				throw new Exception("XML file ($xml) does not exist, no data to transform");
			}
		}

		$this->xmlData = new DOMDocument();
		$this->xmlData->loadXml($xml);
	}

	/**
	 * Adds a parameter to the XSLT processor.
	 *
	 * @param string $name  Name of the parameter to add.
	 * @param string $value Value of the parameter to add.
	 */
	public function addParameter ($name, $value) {
		$this->parameters[$name] = $value;
	}

	/**
	 * Runs an XSL transformation on the provided XML data with the active XSL template.
	 *
	 * @return string The XML result from the XSL transformation as a string.
	 */
	public function process () {
		$processor = new XSLTProcessor();
		$processor->importStyleSheet($this->stylesheet);

		if (is_array($this->parameters)) {
			$processor->setParameter('', $this->parameters);
		}

		return $processor->transformToXML($this->xmlData);
	}
}
?>
