<?php
/**
 * Provides the implementation of a result set using the Smarty template engine to render the data as (X)HTML.
 * To use this result engine you'll need to have Smarty installed in a directory which PHP searches for
 * include files (include_path). You'll also need to add a 'smarty' section in your application configuration,
 * which defines compileDir at the very least. This should be a directory writeable by the PHP process. Other
 * configurable options are cacheDir and configDir which corresponds to Smarty's cache_dir and config_dir
 * respectively. And, if you don't want to use Kolibri's views directory for Smarty templates, you'll
 * need to define templateDir.
 * 
 * @version		$Id$
 */	
class SmartyResult extends AbstractResult {
	private $smartyTemplate;
	
	/**
	 * Constructor.
	 *
	 * @param Object $action	The action processing the request.
	 * @param string $template	Path to the template, relative to the VIEW_PATH, and excluding the extension.
	 */
	public function __construct ($action, $template) {
		parent::__construct($action);
		
		$this->smartyTemplate = "$template.tpl";
	}
	
	/**
	 * Creates a Smarty template engine instance, adds the action and/or request data and
	 * outputs the processed template.
	 *
	 * @param Request $request	Request object representing the current request.
	 */
	public function render ($request) {
		require('Smarty/Smarty.class.php');
		
		$conf = Config::get('smarty');
		
		// Configure the Smarty engine
		$smarty = new Smarty();
		$smarty->template_dir = (isset($conf['templateDir'] ? $conf['templateDir'] : VIEW_PATH);
		$smarty->compile_dir = $conf['compileDir'];
		$smarty->cache_dir = $conf['cacheDir'];
		$smarty->config_dir = $conf['configDir'];
		
		// Assign action data only if it is exposable
		$action = $this->getAction();
		if ($action instanceof Exposable) {
			if (method_exists($action, 'expose')) {
				$smarty->assign($action->expose());
			}
			else {
				$smarty->assign(get_object_vars($action));
			}
		}
		
		// Assign request data
		$smarty->assign($request->expose());
		
		// Output processed template
		$smarty->display($this->smartyTemplate);
	}
}
?>