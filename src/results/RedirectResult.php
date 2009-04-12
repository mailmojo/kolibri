<?php
/**
 * Provides the implementation of a result set which when rendered sends a redirect to the
 * client. It defaults to a 303 (See Other) status code, but this can be overridden.
 */	
class RedirectResult extends AbstractResult {
	private $location;
	private $code;

	/**
	 * Constructor.
	 * 
	 * @param string $location Location of the redirect relative to the web root.
	 * @param int $code        HTTP status code to use. Defaults to 303.
	 */
	public function __construct ($action, $location, $code = 303) {
		parent::__construct($action);
		$this->location = Config::get('webRoot') . $location;
		$this->code = $code;
	}

	/**
	 * Sends the redirect to the client.
	 */
	public function render ($request) {
		header("Location: $this->location", true, $this->code);
		exit;
	}
}
?>
