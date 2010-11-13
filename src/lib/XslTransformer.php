<?php
/**
 * This class provides an abstraction of the XSL transformation capabilities in PHP 5.
 */
class XslTransformer {
	private $stylesheet;
	private $processors;

	/**
	 * Creates a new XSL transformer with a stylesheet to transform data with.
	 *
	 * @param string $xsl	Path to stylesheet.
	 */
	public function __construct ($xsl) {
		if (!file_exists($xsl)) {
			throw new Exception("XSL stylesheet does not exist: $xsl");
		}
		
		/*
		 * Use the XSLT Cache extension (http://code.nytimes.com/projects/xslcache)
		 * if available for up to about 2.5x boost in performance.
		 */
		if (class_exists('xsltCache', false)) {
			$this->stylesheet = $xsl;
			$this->processor = new xsltCache();
		}
		else {
			$this->stylesheet = new DOMDocument();
			$this->stylesheet->load($xsl);
			$this->processor = new XSLTProcessor();
		}
	}

	/**
	 * Adds a parameter to the XSLT processor.
	 *
	 * @param string $name	Name of the parameter to add.
	 * @param string $value Value of the parameter to add.
	 */
	public function addParameter ($name, $value) {
		$this->processor->setParameter('', $name, $value);
	}

	/**
	 * Runs an XSL transformation on the provided XML data with the active XSL template.
	 *
	 * @return string The XML result from the XSL transformation as a string.
	 */
	public function process ($dom) {
		if (!$dom instanceof DOMDocument) {
			throw new Exception('Data to transform is not a DOMDocument.');
		}
		
		$this->processor->importStyleSheet($this->stylesheet);
		return $this->processor->transformToXML($dom);
	}
}
?>
