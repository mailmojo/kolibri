<?php
/**
 * A simple response without a content body, simplifying setting headers directly
 * in the constructor.
 */
class EmptyResponse extends Response {

	/**
	 * Initializes the empty response, setting any headers directly and optionally overriding
	 * the HTTP status code if needed.
	 */
	public function __construct ($headers = array(), $status = 200) {
		$this->status = $status;

		if (!empty($headers)) {
			foreach ($headers as $key => $value) {
				$this->setHeader($key, $value);
			}
		}
	}

	/**
	 * Overrides the default render, only sending headers in the response and no body content.
	 */
	public function render ($request) {
		$this->sendHeaders();
	}
}
