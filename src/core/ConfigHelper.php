<?php
require(ROOT . '/utils/arrays.php');

/**
 * Helper class for reading environment variables, loading configuration files and parsing
 * configuration values.
 */
class ConfigHelper {
	/**
	 * Current environment mode.
	 * @var string
	 */
	private $runMode;

	/**
	 * Pure mapping of interceptor names to classes.
	 * @var array
	 */
	private $interceptorClasses;

	/**
	 * Extended mapping of interceptors, prepared from core and application configurations
	 * and including interceptor stacks.
	 * @var array
	 */
	private $interceptors;

	/**
	 * Creates a new ConfigHelper for a specific environment mode and list of
	 * interceptors that exist.
	 *
	 * @param string $mode              Current environment mode.
	 * @param array $interceptorClasses Active map of interceptor names to classes.
	 * @return ConfigHelper
	 */
	public function __construct ($mode, array $interceptorClasses) {
		$this->runMode = $mode;
		$this->interceptorClasses = $interceptorClasses;
	}

	/**
	 * Loads and returns and array with all application configuration for the current
	 * environment mode. Configuration files are loaded for each environment in a
	 * cascading hierarchy:
	 *   Production -> Development -> Test
	 * The production configuration will always be loaded, but overridden where
	 * neccessary in development and test environments.
	 *
	 * @return array Associative array with all configuration settings for the current
	 *               environment mode.
	 * @throws Exception If application runs in test mode but no database is configured
	 *                   to replace the development database configuration.
	 */
	public function loadApp () {
		// Set up the cascading stack of configuration files depending on current mode
		$configStack = array(Config::PRODUCTION);
		if ($this->runMode != Config::PRODUCTION) {
			$configStack[] = Config::DEVELOPMENT;

			if ($this->runMode == Config::TEST) {
				$configStack[] = Config::TEST;
			}
		}

		$config = array();
		foreach ($configStack as $configMode) {
			$modeConfig = $this->loadMode($configMode);

			/*
			 * We want to prevent accidental use of the development or production database
			 * in the test environment where all data is volatile.
			 */
			if ($configMode == Config::TEST) {
				if (isset($config['database']) && !isset($modeConfig['database'])) {
					throw new Exception('No test database configured to override '
						. 'development database.');
				}
			}

			// Merge config for a specific mode with previously loaded configuration recursively
			$config = array_merge_recursive_distinct($config, $modeConfig);
		}

		/*
		 * The 'app' section in configuration files are automatically flattened to global
		 * configuration values for convenience.
		 */
		if (isset($config['app'])) {
			foreach ($config['app'] as $key => $value) {
				$config[$key] = $value;
			}
			unset($config['app']);
		}

		return $config;
	}

	/**
	 * Loads and returns an array with application configuration for a specific environment
	 * mode.
	 *
	 * @param string $mode Either Config::PRODUCTION, Config::DEVELOPMENT or Config::TEST.
	 * @return array Associative array with configuration for the specified mode.
	 * @throws Exception If the configuration file for the mode does not exist, or
	 *                   there was an error parsing the file (syntax error).
	 */
	private function loadMode ($mode) {
		$file = APP_PATH . "/conf/{$mode}.ini";
		if (!file_exists($file)) {
			throw new Exception("Application configuration file missing for "
				. "{$mode} environment: $file");
		}

		$config = @parse_ini_file($file, true);
		if ($config === false) {
			// Raise the PHP warning from syntax errors in configuration file to an Exception
			$error = error_get_last();
			throw new Exception($error['message']);
		}

		return $config;
	}

	/**
	 * Adds custom interceptor classes to Kolibri's core interceptors.
	 *
	 * @param array $classes Array with interceptor class names, indexed on their shortname.
	 */
	public function addInterceptorClasses (array $classes) {
		$this->interceptorClasses = array_merge($this->interceptorClasses, $classes);
	}

	/**
	 * Merges default interceptor stacks with the application's stacks. Then loops through
	 * all interceptor stacks, adding each stack to the regular interceptor list with the
	 * correct interceptors attached. This makes it possible to use a stack just as a single
	 * interceptor in interceptor mappings.
	 *
	 * @param array $defaultStacks     Default interceptor stacks defined in Kolibri.
	 * @param array $applicationStacks Interceptor stacks defined in the application's
	 *                                 configuration files.
	 * @return array Mapping of interceptors that also include stacks.
	 * @throws Exception If a stack includes the name of a non-existing interceptor.
	 */
	public function prepareInterceptors (array $defaultStacks, array $applicationStacks) {
		$this->interceptors = $this->interceptorClasses;

		// Parse stacks defined in application ini files
		foreach ($applicationStacks as $name => $stack) {
			$applicationStacks[$name] = preg_split('/,\s*/', $stack);
		}

		$stacks = array_merge($defaultStacks, $applicationStacks);
		foreach ($stacks as $name => $stack) {
			/*
			 * Reset stack in case another with the same name exists already,
			 * we don't support merging of individual interceptors for stacks.
			 */
			$this->interceptors[$name] = array();
			foreach ($stack as $interceptor) {
				/*
				 * $interceptor must be the name of an existing interceptor. This gives us
				 * access to the actual interceptor class within the stack.
				 */
				if (isset($this->interceptors[$interceptor])) {
					$this->interceptors[$name][$interceptor]
							= $this->interceptors[$interceptor];
				}
				else {
					throw new Exception("Non-existing interceptor '$interceptor' used "
							. "in stack '$name'");
				}
			}
		}

		return $this->interceptors;
	}

	/**
	 * Parses and validates application specific interceptor settings, and merges them with
	 * the default interceptor settings. Each application specific setting must have a name
	 * including both the name of the interceptor and the property it defines a value for,
	 * separated by a period. I.e. a setting for which model class to use for an authenticated
	 * user would look like this:
	 *   auth.userModel = MyUser
	 *
	 * @param array $defaultSettings Kolibri's default settings for interceptors.
	 * @param array $appSettings     Application specific settings for interceptors.
	 * @return array Associative array with setting names and their values, grouped
	 *               under the name of the interceptor they relate to.
	 * @throws Exception If an application specific setting's name is invalid or incomplete.
	 */
	public function prepareInterceptorSettings (array $defaultSettings, array $appSettings) {
		$parsedAppSettings = array();

		foreach ($appSettings as $setting => $value) {
			/*
			 * Make sure setting name contains one, and only one, period. We depend on the
			 * period to separate the interceptor name from the actual setting's name.
			 */
			if (substr_count($setting, '.') == 1) {
				list($interceptor, $setting) = explode('.', $setting);
				// Make sure the interceptor a setting is defined for actually exists
				if (isset($this->interceptorClasses[$interceptor])) {
					$parsedAppSettings[$interceptor][$setting] = $value;
				}
				else {
					throw new Exception('Settings defined for non-existing interceptor '
							. "'$interceptor' ($file)");
				}
			}
			else {
				throw new Exception("Invalid key '$setting' in interceptor settings ($file)");
			}
		}

		/*
		 * Both the default settings and parsed application settings are arrays indexed
		 * on interceptor names with sub-arrays containing settings for each interceptor.
		 * By merging default and application settings recursively we support overriding
		 * individual settings as well.
		 */
		return array_merge_recursive_distinct($defaultSettings, $parsedAppSettings);
	}

	/**
	 * Prepares interceptor mappings by flattening interceptor stacks and translating
	 * interceptor names to class names. Must be run after prepareInterceptors() for stacks
	 * to be flattened.
	 *
	 * @param array $applicationMappings Associative array that maps URIs to a comma separated
	 *                                   list of interceptors and interceptor stacks.
	 * @return array Map of application URIs to interceptors where stacks are flattened and
	 *               interceptor names converted to their class names.
	 */
	public function prepareInterceptorMappings (array $applicationMappings) {
		foreach ($applicationMappings as $uri => $names) {
			$classes = array();

			/*
			 * Interceptor and stack names in the application's interceptor mapping is
			 * a comma separated list of values.
			 */
			foreach (preg_split('/,\s*/', $names) as $name) {
				/*
				 * If the interceptor or stack name is prefixed by an exclamation mark it
				 * means the interceptor(s) should not be active for the current URI.
				 */
				if ($exclude = ($name{0} == '!')) {
					$name = substr($name, 1);
				}
				$prefix = ($exclude ? '!' : '');

				/*
				 * An array represents a stack of interceptors which we optimize by
				 * flattening to simple interceptor class names.
				 */
				if (is_array($this->interceptors[$name])) {
					foreach ($this->interceptors[$name] as $key => $class) {
						$classes[$key] = $prefix . $class;
					}
				}
				else {
					$classes[$name] = $prefix . $this->interceptors[$name];
				}
			}

			/*
			 * Replace string from application config with optimized array of
			 * interceptor class names.
			 */
			$applicationMappings[$uri] = $classes;
		}

		return $applicationMappings;
	}
}
?>