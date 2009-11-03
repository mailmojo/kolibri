<?php
/**
 * Provides the implementation of a response which sends a redirect to the client when rendered.
 * This defaults to a 302 Found status code. While the status code can be overridden, it is
 * recommended to use a more specific response class where one is availible.
 */
class RedirectResponse extends Response {
	public $location;

	/**
	 * Initialize this response.
	 * 
	 * @param string $location Location of the redirect relative to the web root.
	 * @param int $status      HTTP status code. Defaults to 302 Found.
	 */
	public function __construct ($location, $status = 302) {
		parent::__construct(null, $status);
		$this->location = $location;
	}

	/**
	 * Sends the redirect to the client.
	 */
	public function render ($request) {
		// Prepend webroot if redirect location doesn't include scheme (http, https etc.)
		if (parse_url($this->location, PHP_URL_SCHEME) === null) {
			$this->location = Config::get('webRoot') . $this->location;
		}
		$this->setHeader('Location', $this->location);
		$this->sendHeaders();
		exit;
	}
}
?>
