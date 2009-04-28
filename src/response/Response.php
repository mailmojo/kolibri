<?php
/**
 * Provides a simple implementation of a response. This class can be used directly when
 * outputting data manually, but a more specialized subclass is often used.
 */
class Response {
	protected $data;
	protected $status;
	protected $contentType;
	protected $charset;
	protected $headerCache = array();
	
	/**
	 * Initializes this response with the supplied response data and meta data. When using this
	 * class explicitly, <code>$data</code> must be a string with the response body, or added
	 * through <code>output()</code>. Template implementations usually expects
	 * <code>$data</code> to be an object or array.
	 *
	 * @param mixed $data         Data to use when rendering the output.
	 * @param int $status         HTTP status code. Defaults to 200 OK.
	 * @param string $contentType Content type of the response. Defaults to text/html.
	 * @param string $charset     Charset of the response. Defaults to utf-8.
	 */
	public function __construct ($data = '', $status = 200, $contentType = 'text/html',
			$charset = 'utf-8') {
		$this->data        = $data;
		$this->status      = $status;
		$this->contentType = $contentType;
		$this->charset     = $charset;
		$this->setHeader('Content-Type', "$contentType; charset=$charset");
	}

	/**
	 * Sets a header in the headerCache array.
	 *
	 * @param string $header The header to set.
	 * @param string $value  The value to set.
	 */
	public final function setHeader ($header, $value) {
		$this->headerCache[] = "$header: $value";
	}

	/**
	 * Checks whether the supplied header has been sent or is about to be sent to the client.
	 *
	 * @param string $header The header to check for.
	 * @return bool
	 */
	public final function isHeaderSet ($header) {
		foreach (headers_list() as $headerSent) {
			if (substr($headerSent, 0, strpos($headerSent, ':')) == $header) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Adds content to output. Any previously added data and the content supplied must both
	 * be string values.
	 *
	 * @param string $content Content to add.
	 * @throws Exception      If existing data or content added are not strings.
	 */
	public function output ($content) {
		if (!is_string($this->data)) {
			throw new Exception("Can't add output as previously submittet data
					is not a string.");
		}
		if (!is_string($content)) {
			throw new Exception("Content to output must be a string value.");
		}

		$this->data .= $content . "\n";
	}


	/**
	 * Sends out the buffered headers.
	 */
	protected function sendHeaders() {
		foreach($this->headerCache as $value) {
			header($value, true, $this->status);
		}
	}

	/**
	 * Outputs the response body.
	 */
	public function render ($request) {
		echo $this->data;
	}
}
?>
