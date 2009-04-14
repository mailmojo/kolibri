<?php
/**
 * Provides the implementation of a response which sends a redirect to the client when rendered.
 * This defaults to a 302 Found status code. While the status code can be overridden, it is
 * recommended to use a more specific response class where one is availible.
 */
class RedirectResponse extends Response {
	private $location;

	/**
	 * Initialize this response.
	 * 
	 * @param string $location Location of the redirect relative to the web root.
	 * @param int $status      HTTP status code. Defaults to 302 Found.
	 */
	public function __construct ($location, $status = 302) {
		parent::__construct(null, $status);
		$this->location = Config::get('webRoot') . $location;
	}

	/**
	 * Sends the redirect to the client.
	 */
	public function render ($request) {
		$this->setHeader('Location', $this->location);
		exit;
	}
}
?>
