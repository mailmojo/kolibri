<?php
/**
 * This class encapsulates information about a specific request. GET and POST parameters are
 * made available through convenience methods, as well as the complete URI and method of the
 * request.
 * 
 * @version		$Id: Request.php 1518 2008-06-30 23:43:38Z anders $
 */
class Request implements ArrayAccess, IteratorAggregate, Exposable {
	/**
	 * Request URI.
	 * @var string
	 */
	private $uri;

	/**
	 * Request parameters.
	 * @var array
	 */
	private $params;

	/**
	 * Session associated with this request (if any). Set by a <code>SessionInterceptor</code> if configured.
	 * @var Session
	 */
	//private $session;
	
	/**
	 * HTTP method for this request.
	 * @var string
	 */
	private $method;
	
	/**
	 * Creates an instance of this class. GET and POST parameters are merged. If any parameter keys
	 * conflicts, POST parameters override GET parameters.
	 * 
	 * @param array $get_params		GET parameters for this request.
	 * @param array $post_params	POST parameters for this request.
	 * @param string $uri			URI of this request. Leave empty to use the actual request URI
	 * 								from the client.
	 */
	public function __construct ($getParams, $postParams, $uri = null) {
		$this->params = array_merge($getParams, $postParams);
		$this->method = strtoupper($_SERVER['REQUEST_METHOD']);
		
		// If $uri is empty initialize this request with the URI from the client request
		if (empty($uri)) {
			$uri = $_SERVER['REQUEST_URI'];
			
			// Strip any ?-type GET parameters from the URI (they are in the parameters)
			if (($paramPos = strpos($uri, '?')) !== false) {
				$uri = substr($uri, 0, $paramPos);
			}
		}
		
		$this->uri = rawurldecode($uri);
	}
	
	/**
	 * Checks if a specific request parameter exists.
	 *
	 * @param mixed $key		Request parameter to check.
	 * @return bool				TRUE of the parameter is set, FALSE if not.
	 */
	public function offsetExists ($key) {
		return isset($this->params[$key]);
	}

	/**
	 * Retrieves a specific request parameter, or NULL if not set.
	 *
	 * @param mixed $key		Request parameter to retrieve.
	 * @return mixed			The request parameter, or NULL if not set.
	 */
	public function offsetGet ($key) {
		return (isset($this->params[$key]) ? $this->params[$key] : null);
	}

	/**
	 * Modifying request parameters is disallowed.
	 */
	public function offsetSet ($key, $value) {
		throw new Exception('Modifying request parameters not allowed.');
	}

	/**
	 * Unsetting request parameters is disallowed.
	 */
	public function offsetUnset ($offset) {
		throw new Exception('Unsetting request parameters not allowed.');
	}

	/**
	 * Returns a default iterator which enables iterating over request parameters.
	 *
	 * @return ArrayIterator
	 */
	public function getIterator () {
		return new ArrayIterator($this->params);
	}
	
	/**
	 * Returns the value of the parameter with the specified key, or <code>NULL</code> if the parameter is
	 * not found.
	 * 
	 * @param string $key	Key to the parameter to return.
	 * @return string		Value of the parameter, or <code>NULL</code>.
	 */
	public function get ($key) {
		return (isset($this->params[$key]) ? $this->params[$key] : null);
	}

	/**
	 * Returns all request parameters.
	 *
	 * @return array	Request parameters as key-value pairs.
	 */
	public function getAll () {
		return $this->params;
	}

	/**
	 * Returns the URI of the request.
	 *
	 * @return string	Request URI.
	 */
	public function getUri () {
		return $this->uri;
	}

	/**
	 * Returns the request method (GET or POST).
	 *
	 * @return string	Request method.
	 */
	public function getMethod () {
		return $this->method;
	}

	/**
	 * Sets the session into the request. Performed by a <code>SessionInterceptor</code>.
	 *
	 * @param Session	Session object.
	 */
	//public function setSession ($session) {
	//	$this->session = $session;
	//}

	/**
	 * Returns the session object if set, else <code>NULL</code> is returned.
	 *
	 * @return Session
	 */
	//public function getSession () {
	//	return $this->session;
	//}
	
	/**
	 * Puts all the supplied parameters into the parameters for this request. Should only be used internally
	 * by the framework.
	 *
	 * @param array $params		An associated array with parameters.
	 */
	public function putAll ($params) {
		$this->params = array_merge($this->params, $params);
	}

	/**
	 * Defines our custom hierarchy when exposing this object. The reason we want a different structure is
	 * to provide easy access to request parameters and session data in views. Instead of accessing
	 * request/session/foo, users can more simply use session/foo. Likewise for parameters, which instead
	 * of request/params/foo resides under params/foo. Request-specific meta-data resides under the
	 * request/ element.
	 *
	 * @return array	Exposed structure of this object.
	 */
	public function expose () {
		return array(
				'request'	=> array(
						'uri'		=> $this->uri,
						'method'	=> $this->method
					),
				'params'	=> $this->params
				//'session'	=> $this->session
		);
	}
}
?>
