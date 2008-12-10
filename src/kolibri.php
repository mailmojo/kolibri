<?php
/**
 * The entryway to the Kolibri framework. Essential core files are included, config initialized and
 * processing of the request is started.
 */

/*
 * Defines the root directory of the framework. By default this in a directory named kolibri within the 
 * document root, but this can be changed at will.
 */
define('ROOT', dirname(__FILE__) . '/kolibri');

/*
 * Application specific directories. Modify to your setup if different from the default.
 */
define('APP_PATH', dirname(__FILE__));
define('ACTIONS_PATH', APP_PATH . '/actions');
define('MODELS_PATH', APP_PATH . '/models');
define('VIEW_PATH', APP_PATH . '/views');

// Require essential files. Others are loaded as needed.
require(ROOT . '/core/Config.php');
require(ROOT . '/core/RequestProcessor.php');
require(ROOT . '/core/Dispatcher.php');
require(ROOT . '/core/InterceptorFactory.php');
require(ROOT . '/core/Request.php');

// Init configuration
Config::getInstance();

$kolibri = new RequestProcessor();
$kolibri->process();

/**
 * Loads framework classes and application models as required.
 */
function __autoload ($name) {
	static $autoload;

	if (!isset($autoload)) {
		require(ROOT . '/conf/autoload.php');
		$autoload = $autoloadClasses;
	}

	if (isset($autoload[$name])) {
		require(ROOT . $autoload[$name]);
	}
	else {
		// Class not specified in autoload.php, but may be available in include_path (i.e. /lib)
		if (!import($name)) {
			require($name . '.php');
		}
	}
}

/**
 * Loads model, DAO or util files. Used internally by __autoload(), or by the user for importing utils like 
 * this:
 *
 *   import('urls', 'util');
 */
function import ($file, $type = 'model') {
	static $files;

	switch ($type) {
		case 'model': $path = MODELS_PATH; break;
		case 'dao': $path = MODELS_PATH . '/dao'; break;
		case 'util': $path = ROOT . '/utils'; break;
		default: throw new Exception("Could not import $file. Type $type is unknown.");
	}

	$filePath = $path . "/$file.php";
	if (!isset($files[$filePath])) {
		if (!file_exists($filePath)) {
			return false;
		}

		$files[$filePath] = true;
		require($filePath);
	}

	return true;
}
?>
