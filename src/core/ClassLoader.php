<?php
/**
 * Loads core framework classes and application models with their DAO classes as required.
 */
class ClassLoader {
	/**
	 * Mapping of core class names to the files with their definitions.
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
	 * Initializes the ClassLoader with a mapping of class names to files,
	 * and registers the autoload function with the PHP runtime.
	 */
	public static function initialize ($config) {
		self::$classes = $config;
		spl_autoload_register(array('ClassLoader', 'load'));
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
			// Not in autoload mapping, check to see if it's a model first
			if (file_exists(MODELS_PATH . "/{$className}.php")) {
				require(MODELS_PATH . "/{$className}.php");
			}
			// If not, might be a DAO class
			else if (strtolower(substr($className, -3)) == 'dao') {
				require(MODELS_PATH . "/dao/{$className}.php");
			}
			// If it's not a core, model or DAO class we simply try the include_path
			else {
				@include($className . '.php');
			}
			self::$loaded[$className] = true;
		}
	}
}
?>
