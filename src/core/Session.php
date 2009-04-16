<?php
/**
 * This class encapsulates information about a HTTP session. The session data is not stored
 * within an instance itself, but the global <code>$_SESSION</code> array is abstracted.
 * 
 * The <code>SessionInterceptor</code> should generally be used when using sessions, as it
 * takes care of instantiating this class and injecting it into the request.
 */
class Session implements ArrayAccess, IteratorAggregate {
	/**
	 * Flag indicated whether we have actually started the session.
	 * @var bool
	 */
	private $started;

	// TODO: Add session settings etc. here
	
	/**
	 * Creates an instance of this class. We support lazy-loading of the session, which means
	 * that we don't start the session here unless the user is already in an active session
	 * (determined by a session cookie).
	 */
	public function __construct () {
		if (isset($_COOKIE['PHPSESSID'])) {
			$this->start();
		}
		else $this->started = false;
	}

	/**
	 * Actually starts the session.
	 */
	private function start () {
		session_start();
		$this->started = true;
	}
	
	/**
	 * Checks if a specific session parameter exists.
	 *
	 * @param mixed $key	Session parameter to check.
	 * @return bool			TRUE of the parameter is set, FALSE if not.
	 */
	public function offsetExists ($key) {
		return isset($_SESSION[$key]);
	}

	/**
	 * Returns the value of the session data with the specified key.
	 * 
	 * @param string $key	Key to the value to return.
	 * @return string		Value of the data, or <code>null</code> if not found.
	 */
	public function offsetGet ($key) {
		return $this->get($key);
	}

	/**
	 * Puts a key/value pair into the session.
	 *
	 * @param string $key	Key to associate the value with.
	 * @param mixed $value	Value to store in the session.
	 */
	public function offsetSet ($key, $value) {
		$this->put($key, $value);
	}

	/**
	 * Removes the value associated with the specified key from the session.
	 *
	 * @param string $key	Key associated with the value to remove.
	 */
	public function offsetUnset ($key) {
		$this->remove($key);
	}

	/**
	 * Returns a default iterator which enables iterating over session parameters.
	 *
	 * @return ArrayIterator
	 */
	public function getIterator () {
		return new ArrayIterator($this->started ? $_SESSION : array());
	}

	/**
	 * Returns the value of the session data with the specified key.
	 * 
	 * @param string $key	Key to the value to return.
	 * @return string		Value of the data, or <code>null</code> if not found.
	 */
	public function get ($key) {
		return (isset($_SESSION[$key]) ? $_SESSION[$key] : null);
	}
	
	/**
	 * Returns a reference to all data in this session.
	 * 
	 * @return array	The session data.
	 */
	public function &getAll () {
		return $_SESSION;
	}
	
	/**
	 * Puts a key/value pair into the session.
	 *
	 * @param string $key	Key to associate the value with.
	 * @param mixed $value	Value to store in the session.
	 */
	public function put ($key, $value) {
		if (!$this->started) {
			$this->start();
		}
		$_SESSION[$key] = $value;
	}
	
	/**
	 * Removes the value associated with the specified key from the session.
	 *
	 * @param string $key	Key associated with the value to remove.
	 */
	public function remove ($key) {
		unset($_SESSION[$key]);
	}
	
	/**
	 * Invalidates this session. Any data in the session is destroyed.
	 */
	public function invalidate () {
		session_unset();
		session_destroy();
	}

	/**
	 * Writes the session data.
	 */
	public function write () {
		session_write_close();
	}
}
?>
