<?php
require(ROOT . '/core/Request.php');
require(ROOT . '/core/Dispatcher.php');

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
	 * Object dispatching the request through interceptors to the action.
	 * @var Dispatcher
	 */
	private $dispatcher;

	/**
	 * The action mapper to use for matching this request to an appropriate action.
	 * @var ActionMapper
	 */ 
	private $mapper;

	/**
	 * Initializes a <code>RequestProcessor</code> for the supplied request, and the
	 * action mapper to use during processing.
	 *
	 * @param Request The request to process.
	 */
	public function __construct (Request $request) {
		$this->request = $request;
		$mapperName = $this->findActionMapper();
		$this->mapper = new $mapperName($this->request);
	}

	/**
	 * Process the request and returns the response. The response is also rendered to the
	 * client, unless <code>$render</code> is <code>false</code>.
	 *
	 * @param bool $render Whether the response should be rendered. Defaults to false.
	 * @return Response
	 */
	public function process ($render = true) {
		// Map the request to an action
		$mapping = $this->mapper->map();
		if ($mapping === null) {
			$this->throw404();
		}

		// Create the dispatcher and invoke it
		$this->dispatcher = new Dispatcher($this->request, $mapping);
		$response = $this->dispatcher->invoke();

		// Render the response
		if ($response !== null && $render) {
			ob_start();
			$response->render($this->request);
			ob_end_flush();
		}

		return $response;
	}

	/**
	 * Returns the dispatcher of the current request.
	 *
	 * @return Dispatcher
	 */
	public function getDispatcher () {
		return $this->dispatcher;
	}

	/**
	 * Finds and returns the name of the action mapper to use for this particular request.
	 * 
	 * @see ActionMapper
	 * @return string			Name of the action mapper to use.
	 */
	private function findActionMapper () {
		$actionMappers = Config::getActionMappers();
		
		if ($actionMappers === null) {
			require_once(ROOT . '/core/DefaultActionMapper.php');
			return 'DefaultActionMapper';
		}
		
		$requestUri = $this->request->getUri();
		foreach ($actionMappers as $uri => $mapper) {
			// Replace star wildcard mappings with regex "any characters" mapping, to use regex
			$uri = '#^' . str_replace('*', '.*?', $uri) . '$#';

			if (preg_match($uri, $requestUri) == 1) {
				if ($mapper != 'DefaultActionMapper') {
					// Mapper is application-specific, rely on include_path for loading it
					require_once("$mapper.php");
				}
				else {
					require_once(ROOT . '/core/DefaultActionMapper.php');
				}
				
				return $mapper;
			}
		}
		
		throw new Exception("No ActionMapper configured for requested URI: $requestUri");
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
