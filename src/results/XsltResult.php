<?php
/**
 * Provides the implementation of a result set using XSLT to render the data as XHTML.
 * 
 * @version		$Id: XsltResult.php 1538 2008-08-03 22:10:45Z anders $
 */	
class XsltResult extends AbstractResult {
	private $xslTemplate;
	
	/**
	 * Constructor.
	 *
	 * @param Request &$request		The request object representing the current HTTP request.
	 * @param string $xsl_template	Name of XSL file (excluding the extension) to use, relative to
	 * 								the views-directory.
	 */
	public function __construct ($action, $xslTemplate, $title = null) {
		parent::__construct($action);

		$this->xslTemplate = VIEW_PATH . "$xslTemplate.xsl";
		if (!file_exists($this->xslTemplate)) {
			trigger_error("XSL template ({$this->xslTemplate}) does not exist.", E_USER_ERROR);
		}
	}
	
	/**
	 * Performs the XSL transformations on the XML-data and outputs it.
	 */
	public function render ($request) {
		$xmlGenerator = new XmlGenerator();

		/*
		 * Append the action and request "flattened", because we don't want them wrapped in their own
		 * elements.
		 */
		$xmlGenerator->append($this->getAction(), null, true);
		$xmlGenerator->append($request, null, true);

		$xml = $xmlGenerator->build();

		$transformer = new XslTransformer($this->xslTemplate);
		$transformer->addXml($xml);

		// Add config params to XSLT as parameters
		$config = Config::get();
		foreach ($config as $key => $value) {
			if (!is_array($value)) {
				$transformer->addParameter($key, $value);
			}
		}

		$xhtml = $transformer->process();
		echo $xhtml;
	}
}
?>
