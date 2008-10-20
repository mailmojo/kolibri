<?php
/**
 * This class encapsulates information about the mapping to an action.
 * 
 * The mapping to an action consists of the name of an <code>ActionHandler</code> class and an
 * action within that handler. The path to the action is also availible to ease comparing mappings
 * (i.e. for interceptors). Finally, this class also flags whether the action mapping described by
 * the instance is considered valid. A valid action mapping may be invoked.
 * 
 * @version		$Id: ActionMapping.php 1478 2008-04-02 14:04:52Z anders $
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
	 * Full path to the action class. (Do we care about this?)
	 * @var string
	 */
	private $actionPath;
	
	/**
	 * Creates an instance of this class. An exception is thrown if the action method is not callable.
	 *
	 * @param string $actionPath	Full path to the action class.
	 * @param string $actionMethod	Name of the action method in the action class. One of doGet or doPost.
	 */
	public function __construct ($actionPath, $actionMethod) {
		require_once($actionPath);
		$this->actionPath	= $actionPath;
		$this->actionClass	= basename($actionPath, '.php');
		$this->actionMethod	= $actionMethod;

		if (!method_exists($this->actionClass, $actionMethod)) {
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
