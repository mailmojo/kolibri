<?php
/**
 * This is a more specific redirect response, sending a 301 Moved Permanently status code.
 */
class PermanentRedirectResponse extends RedirectResponse {
	/**
	 * Initialize this response.
	 * 
	 * @param string $location Location of the redirect relative to the web root.
	 */
	public function __construct ($location) {
		parent::__construct($location, 301);
	}
}
?>
