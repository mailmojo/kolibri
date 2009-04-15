<?php
/**
 * This is the main class of the Kolibri framework, which is responsible for initializing the
 * request processing flow.
 * 
 * A <code>Request</code> object is first created, which is passed along to an
 * <code>ActionMapper</code> implementation to map the request to a target action. If a
 * suitable action mapping is found, a <code>Dispatcher</code> is created and invoked to handle
 * the flow of the request. After the dispatcher is done processing the request, any response
 * obtained is rendered to the client.
 */	
class RequestProcessor {
	/**
	 * The request to process.
	 * @var Request
	 */ 
	private $request;

	/**
	 * The action mapper to use for matching this request to an appropriate action.
	 * @var ActionMapper
	 */ 
	private $mapper;

	/**
	 * Create an instance of this class, and find the action mapper to use for this request. 
	 */
	public function __construct () {
		$this->request = new Request($_GET, $_POST);
		$mapperName = $this->findActionMapper();
		$this->mapper = new $mapperName($this->request);
	}

	/**
	 * Process the request.
	 */
	public function process () {
		// Map the request to an action
		$mapping = $this->mapper->map();
		if ($mapping === null) {
			$this->throw404();
		}

		// Create the dispatcher and invoke it
		$this->dispatcher = new Dispatcher($this->request, $mapping);
		$result = $this->dispatcher->invoke();

		// Render the result
		if ($result !== null) {
			ob_start();
			$result->render($this->request);
			ob_end_flush();
		}
	}

	/**
	 * Finds and returns the name of the action mapper to use for this particular request.
	 * 
	 * @see ActionMapper
	 * @return string			Name of the action mapper to use.
	 */
	private function findActionMapper () {
		$actionMappers = Config::getActionMappers();

		foreach ($actionMappers as $uri => $mapper) { // Loop through URIs/mappers
			// Replace star wildcard mappings with regex "any characters" mapping, to use regex
			$uri = '#^' . str_replace('*', '.*?', $uri) . '$#';

			if (preg_match($uri, $this->request->getUri()) == 1) {
				if ($mapper != 'DefaultActionMapper') {
					// Mapper is application-specific, include it (DefaultActionMapper is autoloadable)
					require(APP_PATH . "/mappers/$mapper.php");
				}

				return $mapper;
			}
		}
	}

	/**
	 * Renders a 404 Not Found status page to the client.
	 */
	private function throw404 () {
		$result = new NotFoundResponse();
		$result->render($this->request);
		exit();
	}
}
?>
