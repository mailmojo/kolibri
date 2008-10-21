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
				$data = array_merge($data, $action->expose());
			}
			else {
				$data = array_merge($data, get_object_vars($action));
			}
		}
		// Extract data from the current request
		$data = array_merge($data, $request->expose());
		
		/**
		 * Create a sandbox function which extracts all data to it's local scope,
		 * hiding this result object and the raw request object from the view.
		 */
		$sandbox = create_function('$_t, $_d', <<<FUNC
			extract(\$_d);
			if (ob_start()) {
				@include(\$_t);
				return ob_get_clean();
			}
			return '';
FUNC
		);
		
		echo $sandbox($this->phpTemplate, $data);
	}
}
?>