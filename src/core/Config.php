<?php
require(ROOT . '/core/Autoloader.php');

/**
 * This class represents the configuration of the Kolibri framework.
 *
 * All configuration variables are easily availible through the static methods of this class.
 */
class Config {
	/**
	 * General configuration settings.
	 * @var array
	 */
	private $config;

	/**
	 * Name of interceptors and interceptor stacks mapped to interceptor classes.
	 * @var array
	 */
	private $interceptorClasses;

	/**
	 * Interceptors mapped to actions [action path => interceptors]
	 * @var array
	 * */
	private $interceptorMappings;

	/**
	 * Defines the action mappers responding to specific URIs.
	 * @var array
	 */
	private $actionMappers;

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
		require(ROOT . '/conf/autoload.php');
		require(ROOT . '/conf/interceptors.php');
		require(ROOT . '/conf/validation.php');
		require(APP_PATH . '/conf/config.php');

		$this->config              = $config;
		$this->actionMappers       = $actionMappers;
		$this->interceptorClasses  = $interceptors;
		$this->interceptorMappings = $interceptorMappings;
		$this->validation          = array('classes' => $validators, 'messages' => $validationMessages);

		Autoloader::initialize($autoloadClasses);
		
		/*
		 * Loop through interceptor stacks. For each stack, add the stack to the regular interceptor
		 * list with the correct interceptors attached. This makes it possible to use a stack just as
		 * a single interceptor.
		 */
		foreach ($interceptorStacks as $name => $stack) {
			foreach ($stack as $interceptor) {
				/*
				 * $interceptor must be the name of an existing interceptor. This gives us access
				 * to the actual interceptor class within the stack.
				 */
				$this->interceptorClasses[$name][] = $this->interceptorClasses[$interceptor];
			}
		}

		$incPath = ROOT . '/lib';
		if (isset($this->config['include_path'])) {
			$incPath .= PATH_SEPARATOR . implode(PATH_SEPARATOR, $this->config['include_path']);
		}
		ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $incPath);

		/*
		 * Sets the current locale for date formatting et cetera
		 * XXX: We leave LC_NUMERIC at default, as locales with comma as decimal seperator will
		 * cause SQL queries with floating point values to fail. We should find a better solution...
		 */
		$envLocale = setlocale(LC_NUMERIC, 0);
		setlocale(LC_ALL, $this->config['locale']);
		setlocale(LC_NUMERIC, $envLocale);
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
	 * Loads a configuration file. The new configuration settings will take precidence if there any
	 * conflicts with existing configuration settings.
	 *
	 * @param string $file	Configuration file to load (a PHP file).
	 * @throws Exception	If no config file was specified, or it doesn't exist.
	 */
	public static function load ($file) {
		if (!empty($file) && is_file($file)) {
			require($file);

			if (is_array($config)) {
				$instance = Config::getInstance();
				$instance->config = array_merge($instance->config, $config);
			}
		}
		else {
			throw new Exception('No config-file specified');
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
	 * @return array	Associative array with URIs mapped to action mappers.
	 */
	public static function getActionMappers () {
		$instance = Config::getInstance();
		return $instance->actionMappers;
	}

	/**
	 * Returns the interceptor mappings defined for this application.
	 *
	 * @return array	Associative array with action paths mapped to interceptor names.
	 */
	public static function getInterceptorMappings () {
		$instance = Config::getInstance();
		return $instance->interceptorMappings;
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
