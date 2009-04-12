<?php
/**
 * Provides the implementation of a response which sends a redirect to the client when rendered.
 * It defaults to a 303 (See Other) status code, but this can be overridden.
 */
class RedirectResponse extends Response {
	private $location;

	/**
	 * Initializze this response.
	 * 
	 * @param string $location Location of the redirect relative to the web root.
	 * @param int $status      HTTP status code. Defaults to 303 See Other.
	 */
	public function __construct ($location, $status = 303) {
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
