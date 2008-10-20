<?php
/**
 * This class represents a message to display to the user. The message indicates a successful or
 * failed operation, and a list of specific details can be used to further explain the message.
 * 
 * @version 	$Id: Message.php 1510 2008-06-17 05:45:50Z anders $
 */
class Message implements Exposable {
	/**
	 * The message to display.
	 * @var string
	 */
	public $message;

	/**
	 * Was the operation successful?
	 * @var bool
	 */
	public $success;

	/**
	 * Array with details of the message.
	 * @var array
	 */
	public $details;
	
	/**
	 * Singleton instance of this class.
	 * @var Message
	 */
	private static $instance;

	/**
	 * Creates a new instance.
	 */
	private function __construct () {
		$this->details = array();
	}
	
	/**
	 * Returns an instance of this class with the specified message and success status.
	 * 
	 * An existing instance is returned if one exists, else a new instance is created.
	 */
	public static function getInstance () {
		if (!isset(self::$instance)) {
			self::$instance = new Message();
		}
		return self::$instance;
	}
	
	/**
	 * Checks to see if this message is empty (no message string or details specified).
	 *
	 * @return bool	<code>TRUE</code> if this message is empty, <code>FALSE</code> if not.
	 */
	public function isEmpty () {
		return (empty($this->message) && empty($this->details));
	}
	
	/**
	 * Checks to see if this message indicates a successful operation.
	 * 
	 * @return bool	<code>TRUE</code> if this message represents a success, <code>FALSE</code>
	 * 				otherwise.
	 */	
	public function isSuccessful () {
		return $this->success;
	}
	
	/**
	 * Sets the message this object represents.
	 *
	 * @param string $message		The message itself.
	 * @param bool $success			<code>TRUE</code> if the message indicates a successful
	 * 								operation, <code>FALSE</code> otherwise.
	 */
	public function setMessage ($message, $success = true) {
		$this->message = $message;
		$this->success = $success;
	}
	
	/**
	 * Returns the message this object represents.
	 * 
	 * @return string	The message.
	 */
	public function getMessage () {
		return $this->message;
	}
	
	/**
	 * Adds a detail to this message.
	 *
	 * @param string $detail	The detail string.
	 * @param string $key		Key to associate with detail string. Useful to associate defails
	 * 							with HTML input fields.
	 */
	public function addDetail ($detail, $key = null) {
		if (!empty($key)) {
			$this->details[$key] = $detail;
		}
		else {
			$this->details[] = $detail;
		}
	}
}
?>
