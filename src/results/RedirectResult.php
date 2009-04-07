<?php
/**
 * Provides the implementation of a result set which when rendered sends a redirect to the client.
 */	
class RedirectResult extends AbstractResult {
	private $location;

	/**
	 * Constructor.
	 * 
	 * @param string $location Location of the redirect relative to the web root.
	 */
	public function __construct ($action, $location) {
		parent::__construct($action);
		$this->location = Config::get('webRoot') . $location;
	}

	/**
	 * Sends the redirect to the client.
	 */
	public function render ($request) {
		header("Location: $this->location");
		exit;
	}
}
?>
