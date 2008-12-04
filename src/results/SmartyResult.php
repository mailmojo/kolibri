<?php
require('Smarty/Smarty.class.php');

/**
 * Provides the implementation of a result using the Smarty template engine to render the data as (X)HTML.
 * To use this result engine you'll need to have Smarty installed in a directory where PHP searches for
 * include files (include_path). You'll also need to add a 'smarty' section in your application configuration,
 * which defines compileDir at the very least. An example of the configuration section:
 *
 *   'smarty' => array(
 *     'compileDir'  => '', // Full path to a directory writeable by PHP
 *     'cacheDir'    => '', // Full path to a directory writeable by PHP, if you want to use Smarty caching
 *     'configDir'   => '', // Full path to a directory containing Smarty-specific configuration files
 *     'templateDir' => ''  // Full path to a directory overriding the default Kolibri view directory
 *   )
 *
 * All configurable directories corresponds to similar Smarty directories, ie. compile_dir and cache_dir.
 */	
class SmartyResult extends AbstractResult {
	private $smartyTemplate;
	
	/**
	 * Constructor.
	 *
	 * @param Object $action   The action processing the request.
	 * @param string $template Path to the template, relative to the VIEW_PATH, and excluding the extension.
	 */
	public function __construct ($action, $template) {
		parent::__construct($action);
		
		// Remove prefixed / for Smarty, since it's standard in Kolibri
		if ($template{0} == '/') $template = substr($template, 1);
		
		$this->smartyTemplate = "$template.tpl";
		if (!file_exists(VIEW_PATH . "/$template.tpl")) {
			trigger_error('Smarty template ({$this->smartyTemplate}) does not exist.', E_USER_ERROR);
		}
	}
	
	/**
	 * Creates a Smarty template engine instance. Any exposable action data is made available, as well
	 * as the request object and application configuration.
	 *
	 * @param Request $request Request object representing the current request.
	 */
	public function render ($request) {
		$conf = Config::get('smarty');
		if ($conf === null) {
			trigger_error('Smarty settings missing from application configuration.', E_USER_ERROR);
		}
		
		// Configure the Smarty engine
		$smarty = new Smarty();
		$smarty->template_dir = (isset($conf['templateDir']) ? $conf['templateDir'] : VIEW_PATH);
		$smarty->compile_dir = (isset($conf['compileDir']) ? $conf['compileDir'] : '');
		$smarty->cache_dir = (isset($conf['cacheDir']) ? $conf['cacheDir'] : '');
		$smarty->config_dir = (isset($conf['configDir']) ? $conf['configDir'] : '');
		
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
		$smarty->assign('request', $request);
		
		// Assign application configuration
		$smarty->assign('config', Config::get());
		
		// Output processed template
		$smarty->display($this->smartyTemplate);
	}
}
?>