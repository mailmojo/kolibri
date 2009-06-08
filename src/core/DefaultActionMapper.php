<?php
require(ROOT . '/core/ActionMapping.php');

/**
 * This class is responsible for mapping a request URI to a target action.
 * 
 * This implementation maps the URI to an action by traversing the URI parts (the strings
 * between the / characters) and searching the application's /actions directory for matching
 * directories and files. Parts not explicitly matched are assumed to be IDs unless we have
 * already set an ID for the current URI "section". URI parts after the part that matched
 * a file are also mapped to parameters. The request method determines the actual method
 * to call on the action class, either <code>doGet()</code> or <code>doPost()</code>. This is
 * all easier to explain with a couple of examples:
 *
 *   Given the URI: /lists
 *   Matches the action class: Lists.php <em>or</em> /lists/ListsIndex.php
 *
 *   Given the URI: /lists/1/contacts/4
 *   Matches the action class: /lists/contacts/ListsContactsView.php
 *   And sets request parameters: listsid=1 <em>and</em> contactsid=4
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
	 * Parts of the URI which we map into request parameters are temporarily stored here.
	 * @var array
	 */
	protected $params;

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
	 * @return object <code>ActionMapping</code> representing the action mapped to the
	 *                request, or <code>null</code> if no action could be mapped.
	 */
	public function map () {
		// Remove any prepending / and explode URI into its parts
		$uri = ltrim($this->request->getUri(), '/');
		$uriParts = (empty($uri) ? array() : explode('/', $uri));

		// Map the URI to its target action
		$actionPath = $this->mapAction($uriParts);

		/*
		 * If actionPath is not null, then the URI matched a PHP file. We can then map the
		 * action method and parameters and create the action mapping.
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
	 * @return string			Action method to use.
	 * @throws Exception		If the request method is unsupported.
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
	 * Maps the action class to handle the request specified by the supplied URI.
	 * 
	 * @param array $uri	The request URI in an array.
	 * @return string		The absolute file system path to the action which is to handle the
	 *                      request.
	 */
	protected function mapAction (&$uri) {
		$actionClassPath = ACTIONS_PATH . '/';
		$actionClass = '';
		$previousPart = null;

		// Loop through the URI parts and look for a suitable action
		foreach ($uri as $part) {
			// "CamelCase"-fix for action class name
			$cameledPart = implode(array_map('ucfirst', explode('_', $part)));

			// Check if the classpath + part is a directory
			if ($part != '' && is_dir($actionClassPath . $part)) {
				$actionClassPath .= $part . '/';
				$actionClass .= $cameledPart;
				$previousPart = $part; // Remember this part for next iteration

				array_shift($uri);
			}
			else {
				// For convenience, fix basename of possible action file at this point
				$actionFile = $actionClass . $cameledPart . '.php';

				// Check if the classpath + part is a file
				if (is_file($actionClassPath . $actionFile)) {
					$actionClassPath .= $actionFile;
					$actionClass .= $cameledPart;
					
					array_shift($uri);

					// This part did indeed lead to a specific file, so we return with
					return $actionClassPath;
				}
				/*
				 * Else if a previous part has been set, that means that the previous part
				 * matched a directory, and we can set the current as an id related to that
				 * "section".
				 */
				else if ($previousPart !== null) {
					$this->params[$previousPart . 'id'] = $part;

					// Reset previous part, to disallow several ids for same "section"
					$previousPart = null;
					array_shift($uri);
				}
				/*
				 * Otherwise, the current part could not be matched or set as an id, so we
				 * break out of loop to try a default action.
				 */
				else break;
			}
		}

		/*
		 * We are here if the URI didn't match a specific action class file. If no "previous
		 * part" is present, it means we have an id of which to view a single "item", otherwise
		 * no specific "item" is requested and we look for an index action.
		 */
		$actionClassPath .= $actionClass . ($previousPart === null ? 'View.php' : 'Index.php');
		if (is_file($actionClassPath)) {
			return $actionClassPath;
		}

		return null;
	}

	/**
	 * Maps any URI-specified parameters to the request.
	 * 
	 * @param array $uri	The remaining elements of the request URI (action has been sliced
	 *                      away), specifying the parameters to put in the request.
	 */
	protected function mapParams ($uri) {
		// Step through parameters in the URI, two params at a time (as we map key/value pairs)
		for ($i = 0; $i < count($uri); $i += 2) {
			if (isset($uri[$i + 1])) {
				// There is a next param, take current as key and next as value
				$this->params[urldecode($uri[$i])] = urldecode($uri[$i + 1]);
			}
			else {
				// No next param availible, use the current param as a value with "id" as key
				$this->params['id'] = urldecode($uri[$i]);
			}
		}

		if (!empty($this->params)) {
			// TODO: Do we want URI-params to overwrite GET-params as now, or not?
			$this->request->putAll($this->params);
		}
	}
}
?>
