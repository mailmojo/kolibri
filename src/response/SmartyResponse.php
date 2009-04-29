<?php
require('Smarty/Smarty.class.php');

/**
 * Provides the implementation of a response using the Smarty template engine to render the
 * data as (X)HTML. To use this response engine Smarty must be installed in a directory where
 * PHP searches for include files (include_path). A 'smarty' section must also exist in your
 * application configuration, which defines compileDir at the very least. An example of the
 * configuration section:
 *
 *   [smarty]
 *   compileDir  = "" ; Full path to a PHP-writeable dir
 *   cacheDir    = "" ; Full path to a PHP-writeable dir, if you want to use Smarty caching
 *   configDir   = "" ; Full path to a dir containing Smarty-specific configuration files
 *   templateDir = "" ; Full path to a dir overriding the default Kolibri view directory
 *
 * All configurable directories corresponds to similar Smarty directories, ie. compile_dir and
 * cache_dir.
 */	
class SmartyResponse extends Response {
	private $smartyTemplate;
	
	/**
	 * Initialize this response.
	 *
	 * @param mixed $data         Data to pass on to the Smarty template.
	 * @param string $template    Smarty template to use, relative to VIEW_DIR, omitting the
	 *                            extension.
	 * @param int $status         HTTP status code. Defaults to 200 OK.
	 * @param string $contentType Content type of the response. Defaults to text/html.
	 */
	public function __construct ($data, $template, $status = 200, $contentType = 'text/html') {
		parent::__construct($data, $status, $contentType);
		$this->smartyTemplate = "$template.tpl";

		if (!file_exists(VIEW_PATH . "/$template.tpl")) {
			throw Exception("Smarty template ({$this->smartyTemplate}) does not exist.");
		}
	}
	
	/**
	 * Creates a Smarty template engine instance and uses it to render the output. The data
	 * along with the request object and application configuration is exposed to the template.
	 */
	public function render ($request) {
		$this->sendHeaders();
		
		$conf = Config::get('smarty');
		if ($conf === null) {
			throw new Exception('Smarty settings missing from application configuration.');
		}

		// Configure the Smarty engine
		$smarty = new Smarty();
		$smarty->template_dir = (isset($conf['templateDir']) ? $conf['templateDir'] : VIEW_PATH);
		$smarty->compile_dir = (isset($conf['compileDir']) ? $conf['compileDir'] : '');
		$smarty->cache_dir = (isset($conf['cacheDir']) ? $conf['cacheDir'] : '');
		$smarty->config_dir = (isset($conf['configDir']) ? $conf['configDir'] : '');

		// Assign data we want to expose to Smarty
		$data = array();
		foreach ($this->data as $key => $value) {
			$data[$key] = $value;
		}
		$smarty->assign($data);
		$smarty->assign('request', $request);
		$smarty->assign('config', Config::get());

		// Output processed template
		$smarty->display($this->smartyTemplate);
	}
}
?>
