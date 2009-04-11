<?php
/**
 * Provides the implementation of a response by using XSLT to transform the data server-side
 * before outputting it.
 */
class XsltResponse extends Response {
	private $stylesheet;

	/**
	 * Initialize this response.
	 *
	 * @param mixed $data        Data to transform.
	 * @param string $stylesheet XSL stylesheet to use, relative to VIEW_PATH, omitting the
	 *                           extension.
	 * @param int $status         HTTP status code. Defaults to 200 OK.
	 * @param string $contentType Content type of the response. Defaults to text/html.
	 */
	public function __construct ($data, $stylesheet, $status = 200,
			$contentType = 'text/html') {
		parent::__construct($data, $status, $contentType);
		$this->stylesheet = VIEW_PATH . "$stylesheet.xsl";
	}

	/**
	 * Generates XML of the data, and performs XSLT transformation on it before outputting.
	 */
	public function render ($request) {
		$xmlGenerator = new XmlGenerator();
		// Wrap request data in a containing element, <request />
		$xmlGenerator->append($request, 'request');
		// Append all data directly to the root result element
		$xmlGenerator->append($this->data);

		$transformer = new XslTransformer($this->stylesheet);

		// Add scalar config params to XSLT as parameters
		foreach (Config::get() as $key => $value) {
			if (!is_array($value)) {
				$transformer->addParameter($key, $value);
			}
		}

		echo $transformer->process($xmlGenerator->getDom());
	}
}
?>
