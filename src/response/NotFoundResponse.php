<?php
/**
 * Response implementation which sends a 404 Not Found HTTP status code to the client. A XSL
 * stylesheet is used to render a viewable response to the user.
 */	
class NotFoundResponse extends XsltResponse {
	/**
	 * Initialize this response.
	 *
	 * @param string $stylesheet XSL stylesheet to use.
	 * @param int $status        HTTP status code. Defaults to 200 OK.
	 */
	public function __construct ($stylesheet = '/404', 404) {
		parent::__construct(null, $stylesheet);
	}
}
?>
