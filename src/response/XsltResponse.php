<?php
/**
 * Provides the implementation of a response by using XSLT to transform the data server-side
 * before outputting it.
 */
class XsltResponse extends Response {
	public $stylesheet;

	/**
	 * Initialize this response.
	 *
	 * @param mixed $data         Data to transform.
	 * @param string $stylesheet  XSL stylesheet to use, relative to VIEW_PATH, omitting the
	 *                            extension.
	 * @param int $status         HTTP status code. Defaults to 200 OK.
	 * @param string $contentType Content type of the response. Defaults to text/html.
	 */
	public function __construct ($data, $stylesheet, $status = 200,
			$contentType = 'text/html') {
		parent::__construct($data, $status, $contentType);
		$this->stylesheet = $stylesheet;
	}

	/**
	 * Generates XML of the data, and performs XSL transformation on it before outputting.
	 */
	public function render ($request) {
		$this->sendHeaders();
		
		$stylesheetFile = VIEW_PATH . $this->stylesheet . '.xsl';
		$xmlGenerator = new XmlGenerator();

		// Wrap request data in a containing element, <request />
		$xmlGenerator->append($request, 'request');
		// Append all data directly to the root result element
		$xmlGenerator->append($this->data);

		$transformer = new XslTransformer($stylesheetFile);

		// Add scalar config params to XSLT as parameters
		foreach (Config::get() as $key => $value) {
			if (!is_array($value)) {
				$transformer->addParameter($key, $value);
			}
		}

		// Expose Kolibri mode (production, development or test
		$transformer->addParameter('kolibriMode', Config::getMode());

		echo $transformer->process($xmlGenerator->getDom());
	}
}
?>
