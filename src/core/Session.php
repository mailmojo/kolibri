<?php
/**
 * This class encapsulates information about a HTTP session. The session data is not stored within
 * an instance itself, but the global <code>$_SESSION</code> array is abstracted.
 * 
 * The <code>SessionInterceptor</code> should generally be used when using sessions, as it takes care of
 * instantiating this class and injecting it into the action.
 */
class Session implements ArrayAccess, IteratorAggregate {
	// TODO: Add session settings etc. here
	
	/**
	 * Creates an instance of this class, and prepares the PHP session module for use.
	 */
	public function __construct () {
		if (session_module_name() != 'files') {
			session_module_name('files');
		}
		session_start();
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
	 * Retrieves a specific session parameter, or NULL if not set.
	 *
	 * @param mixed $key	Session parameter to retrieve.
	 * @return mixed		The session parameter, or NULL if not set.
	 */
	public function offsetGet ($key) {
		return (isset($_SESSION[$key]) ? $_SESSION[$key] : null);
	}

	/**
	 * Puts a key/value pair into the session.
	 *
	 * @param string $key	Key to associate the value with.
	 * @param mixed $value	Value to store in the session.
	 */
	public function offsetSet ($key, $value) {
		$_SESSION[$key] = $value;
	}

	/**
	 * Removes the value associated with the specified key from the session.
	 *
	 * @param string $key	Key associated with the value to remove.
	 */
	public function offsetUnset ($key) {
		unset($_SESSION[$key]);
	}

	/**
	 * Returns a default iterator which enables iterating over session parameters.
	 *
	 * @return ArrayIterator
	 */
	public function getIterator () {
		return new ArrayIterator($_SESSION);
	}

	/**
	 * Returns the value of the session data with the specified key.
	 * 
	 * @param string $key	Key to the value to return.
	 * @return string		Value of the data, or <code>FALSE</code> if not found.
	 */
	public function get ($key) {
		return (isset($_SESSION[$key]) ? $_SESSION[$key] : false);
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
