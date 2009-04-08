<?php
/**
 * Loads core framework classes and application models with their DAO classes as required.
 */
class Autoloader {
	/**
	 * Mapping of class names to files with their definitions.
	 * @var array
	 */
	private static $classes = array();
	
	/**
	 * Cache table with names of classes that have been loaded.
	 * Only used for classes not in the autoload class mapping.
	 * @var array
	 */
	private static $loaded = array();
	
	/**
	 * Initializes the Autoloader with a mapping of class names to files,
	 * and registers the autoload function with the PHP runtime.
	 */
	public static function initialize ($config) {
		self::$classes = $config;
		
		spl_autoload_register(array('Autoloader', 'load'));
	}
	
	/**
	 * Kolibris autoload function. Handles autoloading of core framework classes
	 * through a lookup mapping defined in conf/autoload.php. It also handles autoloading
	 * of application model and DAO classes.
	 */
	public static function load ($className) {
		// We optimize for core classes and check if that's what needs loading first.
		if (isset(self::$classes[$className])) {
			require(ROOT . self::$classes[$className]);
		}
		else if (!isset(self::$loaded[$className])) {
			// DAO class names in Kolibri consists of the model name with 'Dao' appended.
			if (substr($className, -3) == 'Dao') {
				require(MODELS_PATH . "/dao/{$className}.php");
			}
			// If it's not a DAO class, see if it's a model class
			else if (file_exists(MODELS_PATH . "/{$className}.php")) {
				require(MODELS_PATH . "/{$className}.php");
			}
			// If it's not a core, model or DAO class we simply use the include_path
			else {
				require($className . '.php');
			}
			self::$loaded[$className] = true;
		}
	}
}
?>