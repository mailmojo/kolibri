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
	 * A list of prioritized paths to search for class files,
	 * initialized from PHP's include_path when needed and cached.
	 */
	private static $includePaths = null;

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
		// We optimize for core classes and check if that's what needs loading first
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
			/*
			 * If it's not a core, model or DAO class we simply try the include_path.
			 * Addendum: If using PHPSpec, errors should be supressed by @include() to
			 * fix issues where equal() tests attempt to include the value no matter
			 * what, which breaks the include when the value isn't a file. When
			 * PHPSpec is gone from our apps for good, this comment can be removed.
			 */
			else {
				/*
				 * Expand _ in class names to / directory separators. We only expand when the
				 * letter following the underscore is a capital letter, as that is the normal
				 * convension for class names with underscores in a directory hierarchy.
				 */
				$className = preg_replace("/_(?=[A-Z])/", "/", $className);
				if (($file = self::findFile($className)) !== null) {
					include($file);
				}
			}
			self::$loaded[$className] = true;
		}
	}

	/**
	 * Internal method for searching PHP's include path as a last resort for
	 * including a class.
	 * @param string $className Name of the class to search for.
	 * @return string The path of the file if found, NULL otherwise.
	 */
	private static function findFile ($className) {
		if (self::$includePaths === null) {
			self::$includePaths = explode(PATH_SEPARATOR, get_include_path());
			foreach (self::$includePaths as $i => $path) {
				self::$includePaths[$i] = realpath($path);
			}
		}

		foreach (self::$includePaths as $path) {
			$file = $path . DIRECTORY_SEPARATOR . $className . '.php';
			if (file_exists($file)) {
				return $file;
			}
		}
		return null;
	}
}
?>
