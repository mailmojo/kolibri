<?php
/**
 * Provides the implementation of a response returning a XML document representing the data
 * supplied.
 */
class XmlResponse extends Response {
	/**
	 * Initialize this response.
	 *
	 * @param mixed $data         Data to transform.
	 * @param string $stylesheet  XSL stylesheet to use, relative to VIEW_PATH, omitting the
	 *                            extension.
	 * @param int $status         HTTP status code. Defaults to 200 OK.
	 * @param string $contentType Content type of the response. Defaults to text/html.
	 */
	public function __construct ($data, $status = 200, $contentType = 'application/xml') {
		parent::__construct($data, $status, $contentType);
	}

	/**
	 * Outputs an XML document with the data, wrapped in a root 'result' element.
	 */
	public function render ($request) {
		$this->sendHeaders();

		$xmlGenerator = new XmlGenerator();
		$xmlGenerator->append($this->data);
		echo $xmlGenerator->getXml();
	}
}
?>
