<?php
/**
 * This is a more specific redirect response, sending a 303 See Other status code. This is
 * most correctly used when redirecting to another GET location to display the result of a
 * POST.
 */
class SeeOtherResponse extends RedirectResponse {
	/**
	 * Initialize this response.
	 * 
	 * @param string $location Location of the redirect relative to the web root.
	 */
	public function __construct ($location) {
		parent::__construct($location, 303);
	}
}
?>
