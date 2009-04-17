<?php
require(ROOT . '/core/ActionMapping.php');

/**
 * This class is responsible for mapping a request URI to a target action.
 * 
 * This implementation maps the
 * request URI to an action by first traversing the URI parts (the strings between the / characters)
 * and looking for an action handler ........
 * 
 * TODO: Complete comment
 * 
 * @version		$Id: DefaultActionMapper.php 1530 2008-07-21 15:10:08Z anders $
 */	
class DefaultActionMapper {
	/**
	 * The request being mapped.
	 * @var Request
	 */ 
	protected $request;

	/**
	 * The action mapping determined after mapping the request.
	 * @var ActionMapping
	 */
	protected $mapping;

	/**
	 * Create an instance of this class and initialize an <code>ActionMapping</code>.
	 */
	public function __construct ($request) {
		$this->request = $request;
	}

	/**
	 * Maps the request to its target action and returns the <code>ActionMapping</code>.
	 * If the request could not be mapped to an action, <code>NULL</code> is returned.
	 *
	 * @return object	<code>ActionMapping</code> representing the action mapped to the request,
	 * 					or <code>NULL</code> if no action could be mapped.
	 */
	public function map () {
		/*
		 * First strip scheme and hostname from webRoot, to ensure webRoot is an URI path like
		 * the request URI. We then "normalize" the URI to be within the webRoot.
		 */
		$absoluteUri = parse_url(Config::get('webRoot'), PHP_URL_PATH);
		$uri = trim(str_replace($absoluteUri, '', $this->request->getUri()), '/');
		$uriParts = (empty($uri) ? array() : explode('/', $uri));

		// Map the URI to its target action
		$actionPath = $this->mapAction($uriParts);

		/*
		 * If actionPath is not null, then the URI matched a PHP file. We can then map the action method
		 * and parameters and create the action mapping.
		 */
		if ($actionPath !== null) {
			$actionMethod = $this->mapMethod($this->request->getMethod());
			$this->mapping = new ActionMapping($actionPath, $actionMethod);
			$this->mapParams($uriParts);

			return $this->mapping;
		}

		// No action was mapped for this request
		return null;
	}

	/**
	 * Maps the request method to an action method. We currently only support GET and POST.
	 *
	 * @param string $method	Request method.
	 * @throws Exception		If the request method is unsupported.
	 * @return string			Action method to use.
	 */
	protected function mapMethod ($method) {
		if ($method == 'GET') {
			return 'doGet';
		}
		else if ($method == 'POST') {
			return 'doPost';
		}
		else {
			throw new Exception("Request method $method is not supported");
		}
	}

	/**
	 * Maps the action handler to handle the request specified by the supplied URI.
	 * 
	 * @param array $uri	The request URI in an array.
	 * @return string		The full file system path to the action which is to handle the
	 * 						request.
	 */
	protected function mapAction (&$uri) {
		// Get the initial path to the handlers directory
		$actionPath = ACTIONS_PATH . '/';
		$index = 0;

		// Loop through the URI parts and look for a suitable action
		foreach ($uri as $part) {
			if ($part != '' && is_dir($actionPath . $part)) {
				// Current part is a directory, append to action path and shift directory off the URI
				$actionPath .= $part . '/';
				array_shift($uri);
			}
			else {
				// "CamelCase"-fix for action name.
				$actionName = implode(array_map('ucfirst', explode('_', $part)));
				$actionFile = $actionName . '.php';

				if (is_file($actionPath . $actionFile)) {
					// Element is specific action. Append element to action path.
					$actionPath .= $actionFile;
					
					// Shift the action name off URI
					array_shift($uri);
					return $actionPath;
				}

				break; // Current part was not found as action, break out of loop to try default action
			}
		}

		/*
		 * We are here if the latest URI part that was matched is an existing directory. Check to see
		 * if a default action within that directory exists.
		 */
		$actionPath .= 'Index.php';
		if (is_file($actionPath)) {
			return $actionPath;
		}

		return null;
	}

	/**
	 * Maps any URI-specified parameters to the request.
	 * 
	 * @param array $uri	The remaining elements of the request URI (action has been sliced away),
	 *						specifying the parameters to put in the request.
	 */
	protected function mapParams ($uri) {
		$params = array();

		// Step through parameters in the URI, two params at a time (as we map key/value pairs)
		for ($i = 0; $i < count($uri); $i += 2) {
			if (isset($uri[$i + 1])) {
				// There is a next param, take current as key and next as value
				$params[urldecode($uri[$i])] = urldecode($uri[$i + 1]);
			}
			else {
				// No next param availible, use the current param as a value with "id" as key
				$params['id'] = urldecode($uri[$i]);
			}
		}

		if (!empty($params)) {
			// TODO: Do we want URI-params to overwrite GET-params as now, or not?
			$this->request->putAll($params);
		}
	}
}
?>
