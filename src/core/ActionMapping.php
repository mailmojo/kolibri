<?php
/**
 * This class encapsulates information about the mapping to an action.
 * 
 * The mapping to an action consists of the name of the action class and the action method
 * within that class or a callable function.
 */
class ActionMapping {
	/**
	 * Name of the action class of this request.
	 * @var string
	 */
	private $actionClass;

	/**
	 * Name of the action method that will be executed. One of doGet or doPost.
	 * @var string
	 */
	private $actionMethod;

	/**
	 * Full path to the action class.
	 * XXX: This is currently unused except when requiring the file. Do we care about this?
	 * @var string
	 */
	private $actionPath;
	
	/**
	 * Creates an instance of this class. An exception is thrown if the action method is not
	 * callable.
	 *
	 * @param string $actionPath	Full path to the action class.
	 * @param string $actionMethod	Name of the action method in the action class, or an actual callable.
	 */
	public function __construct ($actionPath, $actionMethod) {
		require($actionPath);
		$this->actionPath	= $actionPath;
		$this->actionClass	= basename($actionPath, '.php');
		$this->actionMethod	= $actionMethod;

		if (!is_callable($actionMethod) && !method_exists($this->actionClass, $actionMethod)) {
			throw new Exception("No callable action method $actionMethod found in $actionPath");
		}
	}

	public function getActionClass () {
		return $this->actionClass;
	}

	public function getActionMethod () {
		return $this->actionMethod;
	}
}
?>
