<?php
/**
 * Provides the implementation of using a plain PHP template file for creating the result.
 * 
 * @version		$Id$
 */	
class PhpResult extends AbstractResult {
	private $phpTemplate;
	
	/**
	 * Constructor.
	 *
	 * @param Object $action		The action processing the request.
	 * @param string $phpTemplate	Path to the template, relative to the VIEW_PATH, and excluding the extension.
	 */
	public function __construct ($action, $phpTemplate) {
		parent::__construct($action);
		
		$this->phpTemplate = VIEW_PATH . "/$phpTemplate.php";
		if (!file_exists($this->phpTemplate)) {
			trigger_error("PHP template ({$this->phpTemplate}) does not exist.", E_USER_ERROR);
		}
	}
	
	/**
	 * Extracts data from the current action and/or request into a sandboxed function scope.
	 * This sandboxed scope is made available to the PHP template file by including it directly, and
	 * collecting it's output which is thus used as the results output.
	 *
	 * @param Request $request	Request object representing the current request.
	 */
	public function render ($request) {
		$data = array();
		
		// Extract data from the current action if it is exposable
		$action = $this->getAction();
		if ($action instanceof Exposable) {
			if (method_exists($action, 'expose')) {
				$data = $action->expose();
			}
			else {
				$data = get_object_vars($action);
			}
		}
		
		/**
		 * Create a sandbox function which extracts all data to it's local scope,
		 * instead of letting the view template run inside the PhpResult object scope.
		 */
		$sandbox = create_function('$request, $_d, $_t', 'extract($_d); @include($_t);');
		$sandbox($request, $data, $this->phpTemplate);
	}
}
?>