<?php
require(ROOT . '/core/Autoloader.php');

/**
 * This class represents the configuration of the Kolibri framework.
 *
 * All configuration variables are easily available through the static methods of this class.
 */
class Config {
	/**
	 * Constants for the different environment modes an application can be in.
	 * Development is default, but can be changed through KOLIBRI_ENV environment
	 * variable or by calling Config::setMode().
	 */
	const DEVELOPMENT = 'development';
	const TEST = 'test';
	const PRODUCTION = 'production';
	
	/**
	 * General configuration settings.
	 * @var array
	 */
	private $config = array();

	/**
	 * Name of interceptors and interceptor stacks mapped to interceptor classes.
	 * @var array
	 */
	private $interceptorClasses;

	/**
	 * Defines the validation configuration (validator classes and validation messages).
	 * @var array
	 */
	private $validation;

	/**
	 * Singleton instance of this class.
	 * @var Config
	 */
	private static $instance;

	/**
	 * Private constructor which initializes the configuration. It is defined private as all
	 * interaction with this class goes through static methods.
	 */
	private function __construct () {
		// Define constants for application specific directories
		define('ACTIONS_PATH', APP_PATH . '/actions');
		define('MODELS_PATH', APP_PATH . '/models');
		define('VIEW_PATH', APP_PATH . '/views');

		// Require Kolibri core configuration files.
		require(ROOT . '/conf/autoload.php');
		require(ROOT . '/conf/interceptors.php');
		require(ROOT . '/conf/validation.php');

		// Initialize the Kolibri class autoloader with class mappings from conf/autoload.php
		Autoloader::initialize($autoloadClasses);

		/*
		 * Loop through interceptor stacks. For each stack, add the stack to the regular interceptor
		 * list with the correct interceptors attached. This makes it possible to use a stack just as
		 * a single interceptor.
		 */
		$this->interceptorClasses  = $interceptors;
		foreach ($interceptorStacks as $name => $stack) {
			foreach ($stack as $interceptor) {
				/*
				 * $interceptor must be the name of an existing interceptor. This gives us access
				 * to the actual interceptor class within the stack.
				 */
				$this->interceptorClasses[$name][] = $this->interceptorClasses[$interceptor];
			}
		}

		// Store validation configuration from conf/validation.php
		$this->validation = array('classes' => $validators, 'messages' => $validationMessages);

		// Set the currently active environment mode, default is development mode
		if (($env_mode = getenv('KOLIBRI_ENV')) && self::validateMode($env_mode)) {
			$this->mode = $env_mode;
		}
		else {
			$this->mode = self::DEVELOPMENT;
		}
		
		$this->loadApp();
	}

	/**
	 * Loads all application configuration for the current environment mode.
	 * Configuration files are loaded for each environment in a hierarchy:
	 *   Production -> Development -> Test
	 * The production configuration will always be loaded, but overridden where
	 * neccessary in development and test environments.
	 */
	private function loadApp () {
		$configStack = array(Config::PRODUCTION);
		if ($this->mode == Config::DEVELOPMENT || $this->mode == Config::TEST) {
			$configStack[] = Config::DEVELOPMENT;
		}
		if ($this->mode == Config::TEST) {
			$configStack[] = Config::TEST;
		}

		foreach ($configStack as $configFile) {
			$this->load($configFile);
		}
		
		$incPath = ROOT . '/lib';
		if (isset($this->config['includePath'])) {
			$incPath .= PATH_SEPARATOR . implode(PATH_SEPARATOR, (array) $this->config['includePath']);
		}
		ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $incPath);
		
		/*
		 * Sets the current locale for date formatting et cetera
		 * XXX: We leave LC_NUMERIC at default, as locales with comma as decimal seperator will
		 * cause SQL queries with floating point values to fail. We should find a better solution...
		 */
		if (isset($this->config['locale'])) {
			$envLocale = setlocale(LC_NUMERIC, 0);
			setlocale(LC_ALL, $this->config['locale']);
			setlocale(LC_NUMERIC, $envLocale);
		}
	}
	
	/**
	 * Loads the application's configuration file for a specific environment mode.
	 * The configuration values loaded will be merged recursively with any previously
	 * loaded configuration.
	 *
	 * @param string $mode Either Config::PRODUCTION, Config::DEVELOPMENT or Config::TEST.
	 * @throws Exception   If the configuration file for the mode does not exist, or
	 *                     there was an error parsing the file (syntax error).
	 */
	private function load ($mode) {
		$file = APP_PATH . "/conf/{$mode}.ini";
		if (!file_exists($file)) {
			throw new Exception("Application configuration file missing for {$mode} environment: $file");
		}
		
		$config = @parse_ini_file($file, true);
		if (is_array($config)) {
			/*
			 * The 'app' section in configuration files are always automatically flattened
			 * to global configuration values for convenience.
			 */
			if (isset($config['app'])) {
				foreach ($config['app'] as $key => $value) {
					$config[$key] = $value;
				}
				unset($config['app']);
			}

			/*
			 * We want to prevent accidental use of the development or production database
			 * in the test environment where all data is volatile.
			 */
			if ($mode == self::TEST) {
				if (isset($this->config['database']) && !isset($config['database'])) {
					throw new Exception('No test database configured to override development database.');
				}
			}
			
			// Merge newly loaded configuration with previously loaded configuration recursively
			Utils::import('arrays');
			$this->config = array_merge_recursive_distinct($this->config, $config);
		}
		else if ($config === false) {
			// Raise the PHP warning from syntax errors in configuration file to an Exception
			$error = error_get_last();
			throw new Exception($error['message']);
		}
	}
	
	/**
	 * Returns an instance of this class. An existing instance is returned if one exists, else a new
	 * instance is created.
	 * 
	 * @return Config
	 */
	public static function getInstance () {
		if (!isset(self::$instance)) {
			self::$instance = new Config();
		}
		return self::$instance;
	}

	/**
	 * Returns the environment mode Kolibri is currently running in.
	 *
	 * @return string Either Config::DEVELOPMENT, Config::TEST or Config::PRODUCTION.
	 */
	public static function getMode () {
		$instance = Config::getInstance();
		return $instance->mode;
	}
	
	/**
	 * Sets the active environment mode for Kolibri during runtime.
	 * Note that the environment mode should be set as early as possible. The best way
	 * is by setting the KOLIBRI_ENV environment variable.
	 *
	 * @param string $mode One of Config::DEVELOPMENT, Config::TEST or Config::PRODUCTION.
	 * @throws Exception   If the mode specified is not a recognized mode.
	 */
	public static function setMode ($mode) {
		if (self::validateMode($mode)) {
			$instance = Config::getInstance();
			$instance->mode = $mode;
			// XXX: Reload app configuration?
		}
	}
	
	private static function validateMode ($mode) {
		if ($mode == Config::DEVELOPMENT || $mode == Config::TEST || $mode == Config::PRODUCTION) {
			return true;
		}
		else {
			throw new Exception("Invalid Kolibri environment mode: $mode");
		}
	}
	
	/**
	 * Returns the value of the configuration setting with the specified key, or <code>NULL</code> if
	 * not found. If no key is supplied, all settings are returned.
	 * 
	 * @param string $key	Key of the configuration value to return, or <code>NULL</code> ro retrieve all.
	 * @return mixed		Configuration variable, <code>NULL</code> og array of all variables.
	 */
	public static function get ($key = null) {
		$instance = Config::getInstance();
		if ($key !== null) {
			return (isset($instance->config[$key]) ? $instance->config[$key] : null);
		}
		return $instance->config;
	}

	/**
	 * Returns the names of the action mappers defined for this application.
	 *
	 * @return mixed Associative array with URIs mapped to action mappers, or <code>NULL</code> when
	 *               none is defined.
	 */
	public static function getActionMappers () {
		$instance = Config::getInstance();
		return (isset($instance->config['actionmappers']) ? $instance->config['actionmappers'] : null);
	}

	/**
	 * Returns the interceptor mappings defined for this application.
	 *
	 * @return array Associative array with action paths mapped to interceptor names.
	 */
	public static function getInterceptorMappings () {
		$instance = Config::getInstance();
		return (isset($instance->config['interceptors']) ? $instance->config['interceptors'] : array());
	}

	/**
	 * Returns an array with the class of an interceptor or classes of an interceptor stack.
	 *
	 * @param string $key	Key of interceptor or interceptor stack to get classes for.
	 * @return array		Array of class names.
	 */
	public static function getInterceptorClasses ($key) {
		$instance = Config::getInstance();

		if (!empty($instance->interceptorClasses[$key])) {
			$class = $instance->interceptorClasses[$key];

			/*
			 * We must check if index 0 is not set in addition to the regular array-check, as $class can
			 * be an interceptor definition with parameters (an array with a class name as the key and an
			 * array of interceptor parameters as the value). We want to wrap those in an array as well,
			 * hence the extra check.
			 */
 			 if (!is_array($class) || !isset($class[0])) {
				return array($class);
			}
			return $class;
		}
		return array();
	}

	/**
	 * Returns an array of validation configuration.
	 *
	 * @return array		Array of validation configuration.
	 */
	public static function getValidationConfig () {
		$instance = Config::getInstance();
		return $instance->validation;
	}
}
?>
