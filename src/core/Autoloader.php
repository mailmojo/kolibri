<?php
/**
 * Loads framework classes and application models as required.
 */
class Autoloader {
	private static $classes;
	private static $loaded;
	
	public static function initialize ($config) {
		self::$classes = $config;
		self::$loaded = array();
		
		spl_autoload_register(array('Autoloader', 'load'));
	}
	
	public static function load ($className) {
		if (isset(self::$classes[$className])) {
			require(ROOT . self::$classes[$className]);
		}
		else if (!isset(self::$loaded[$className])) {
			if (substr($className, -3) == 'Dao') {
				require(MODELS_PATH . "/dao/{$className}.php");
			}
			else if (file_exists(MODELS_PATH . "/{$className}.php")) {
				require(MODELS_PATH . "/{$className}.php");
			}
			else {
				require($className . '.php');
			}
			self::$loaded[$className] = true;
		}
	}
}
?>