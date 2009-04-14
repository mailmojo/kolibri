<?php
/**
 * Helper class for reading environment variables, loading configuration files and parsing
 * configuration values.
 */
class ConfigHelper {
	/**
	 * Determines and returns the current environment mode. Checks for an environment variable
	 * named KOLIBRI_MODE and uses it's value if it's one of the three supported environment
	 * modes. In any other case the default environment mode is development.
	 *
	 * @throws Exception If KOLIBRI_MODE contains an unsupported environment mode.
	 */
	public static function getMode () {
		if (($envMode = getenv('KOLIBRI_MODE')) !== false) {
			if ($envMode == Config::PRODUCTION
					|| $envMode == Config::DEVELOPMENT
					|| $envMode == Config::TEST) {
				return $envMode;
			}
			else {
				throw new Exception("Invalid environment mode in \$KOLIBRI_MODE: '$envMode'");
			}
		}

		return Config::DEVELOPMENT;
	}
	
	/**
	 * Loads all application configuration for the current environment mode.
	 * Configuration files are loaded for each environment in a hierarchy:
	 *   Production -> Development -> Test
	 * The production configuration will always be loaded, but overridden where
	 * neccessary in development and test environments.
	 *
	 * @param string $runtimeMode Current environment mode, usually as determined through
	 *                            ConfigHelper::getMode().
	 * @return array Associative array with all configuration settings for the current
	 *               environment mode.
	 * @throws Exception If application runs in test mode but no database is configured
	 *                   to replace the development database configuration.
	 */
	public static function loadApp ($runtimeMode) {
		Utils::import('arrays');
		
		// Set up the cascading stack of configuration files depending on current mode
		$configStack = array(Config::PRODUCTION);
		if ($runtimeMode != Config::PRODUCTION) {
			$configStack[] = Config::DEVELOPMENT;
			
			if ($runtimeMode == Config::TEST) {
				$configStack[] = Config::TEST;
			}
		}
		
		$config = array();
		foreach ($configStack as $configMode) {
			$modeConfig = self::loadMode($configMode);
			
			/*
			 * We want to prevent accidental use of the development or production database
			 * in the test environment where all data is volatile.
			 */
			if ($configMode == Config::TEST) {
				if (isset($config['database']) && !isset($modeConfig['database'])) {
					throw new Exception('No test database configured to override development database.');
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
	 * Loads the application's configuration file for a specific environment mode.
	 *
	 * @param string $mode Either Config::PRODUCTION, Config::DEVELOPMENT or Config::TEST.
	 * @throws Exception   If the configuration file for the mode does not exist, or
	 *                     there was an error parsing the file (syntax error).
	 */
	public static function loadMode ($mode) {
		$file = APP_PATH . "/conf/{$mode}.ini";
		if (!file_exists($file)) {
			throw new Exception("Application configuration file missing for {$mode} environment: $file");
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
	 * Merges default interceptor stacks with the application's stacks. Then loops through
	 * all interceptor stacks, adding each stack to the regular interceptor list with the
	 * correct interceptors attached. This makes it possible to use a stack just as a single
	 * interceptor in interceptor mappings.
	 *
	 * @param array $classes           Mapping of interceptor names to interceptor classes.
	 * @param array $defaultStacks     Default interceptor stacks defined in Kolibri.
	 * @param array $applicationStacks Interceptor stacks defined in the application's
	 *                                 configuration files.
	 * @return array Mapping of interceptors that also include stacks.
	 * @throws Exception If a stack includes the name of a non-existing interceptor.
	 */
	public static function prepareInterceptors (array $classes, array $defaultStacks,
			array $applicationStacks) {
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
			$classes[$name] = array();
			foreach ($stack as $interceptor) {
				/*
				 * $interceptor must be the name of an existing interceptor. This gives us access
				 * to the actual interceptor class within the stack.
				 */
				if (isset($classes[$interceptor])) {
					$classes[$name][$interceptor] = $classes[$interceptor];
				}
				else {
					throw new Exception("Non-existing interceptor '$interceptor' used "
							. "in stack '$name'");
				}
			}
		}
		
		return $classes;
	}
	
	/**
	 * Parses and validates application specific interceptor settings, and merges them with
	 * the default interceptor settings. Each application specific setting must have a name
	 * including both the name of the interceptor and the property it defines a value for,
	 * separated by a period. Ie. a setting for which model class to use for an authenticated
	 * user would look like this:
	 *   auth.userModel = MyUser
	 *
	 * @param array $classes             Mapping of interceptor names to interceptor classes.
	 * @param array $defaultSettings     Kolibri's default settings for interceptors.
	 * @param array $applicationSettings Application specific settings for interceptors.
	 * @param array $settings Associative array with setting names and their values, grouped
	 *                        under the name of the interceptor they relate to.
	 * @throws Exception If an application specific setting's name is invalid or incomplete.
	 */
	public static function prepareInterceptorSettings (array $classes, array $defaultSettings,
			array $applicationSettings) {
		Utils::import('arrays');
		
		$settings = array();
		foreach ($applicationSettings as $setting => $value) {
			if (substr_count($setting, '.') == 1) {
				list($interceptor, $setting) = explode('.', $setting);
				if (isset($classes[$interceptor])) {
					$settings[$interceptor][$setting] = $value;
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
		
		return array_merge_recursive_distinct($defaultSettings, $settings);
	}
	
	/**
	 * Prepares interceptor mappings by flattening interceptor stacks and translating
	 * interceptor names to class names.
	 *
	 * @param array $interceptors        Complete list of interceptors and interceptor stacks.
	 * @param array $applicationMappings Mapping of application URIs to interceptors.
	 * @return array Map of application URIs => interceptors where stacks are flattened and
	 *               interceptor names converted to their class names.
	 */
	public static function prepareInterceptorMappings (array $interceptors, array $applicationMappings) {
		foreach ($applicationMappings as $uri => $names) {
			$classes = array();
			foreach (preg_split('/,\s*/', $names) as $name) {
				if ($name{0} == '!') {
					$prefix = '!';
					$name = substr($name, 1);
				}
				else {
					$prefix = '';
				}
				
				if (is_array($interceptors[$name])) {
					$classes = array_merge($classes, $interceptors[$name]);
				}
				else {
					$classes[$name] = $prefix . $interceptors[$name];
				}
			}
			
			$applicationMappings[$uri] = $classes;
		}

		return $applicationMappings;
	}
}
?>