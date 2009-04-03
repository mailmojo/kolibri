<?php
/**
 * Provides the implementation of a result set using XSLT to render the data.
 * 
 * @version		$Id$
 */	
class XsltResult extends AbstractResult {
	private $xslTemplate;
	
	/**
	 * Constructor.
	 *
	 * @param object $action      Current action.
	 * @param string $xslTemplate Name of XSL file (excluding the extension) to use, relative to
	 *                            the views-directory.
	 */
	public function __construct ($action, $xslTemplate) {
		parent::__construct($action);

		$this->xslTemplate = VIEW_PATH . "$xslTemplate.xsl";
		if (!file_exists($this->xslTemplate)) {
			throw new Exception("XSL template ({$this->xslTemplate}) does not exist.");
		}
	}
	
	/**
	 * Performs the XSL transformations on the XML-data and outputs it.
	 */
	public function render ($request) {
		// Convert the request and action data to XML for XSL transformation
		$xmlGenerator = new XmlGenerator();
		// Wrap request data in a containing element, <request />
		$xmlGenerator->append($request, 'request');
		// Append all action data directly to the root result element
		$xmlGenerator->append($this->action);
		
		$transformer = new XslTransformer($this->xslTemplate);
		$transformer->addXml($xmlGenerator->getXml());

		// Add config params to XSLT as parameters
		foreach (Config::get() as $key => $value) {
			if (!is_array($value)) {
				$transformer->addParameter($key, $value);
			}
		}

		echo $transformer->process();
	}
}
?>
