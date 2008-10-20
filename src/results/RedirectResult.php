<?php
/**
 * Provides the implementation of a result set which when rendered sends a redirect to the client.
 * 
 * @version		$Id: RedirectResult.php 1518 2008-06-30 23:43:38Z anders $
 */	
class RedirectResult extends AbstractResult {
	private $location;

	/**
	 * Constructor.
	 * 
	 * @param string $location		Location of the redirect relative to the web root.
	 */
	public function __construct ($action, $location) {
		parent::__construct($action);
		$this->location = Config::get('webRoot') . $location;
	}

	/**
	 * Sends the redirect to the client.
	 */
	public function render ($request) {
		$action = $this->getAction();

		/*
		 * If a session is active and the action has a message, store them temporarily in the
		 * session through the redirect.
		 */
		if ($action instanceof SessionAware) {

			if ($action instanceof MessageAware && !$action->msg->isEmpty()) {
				$action->session['message'] = $action->msg;
			}
			// See ValidationInterceptor for reason this is commented away
			//if ($action instanceof ValidationAware && !empty($action->errors)) {
			//	$session->put('errors', $action->errors);
			//}

			$action->session->write();
		}

		header("Location: $this->location");
		exit;
	}
}
?>
