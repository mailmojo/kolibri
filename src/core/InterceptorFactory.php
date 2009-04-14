<?php
/**
 * This is a helper class used to create interceptors.
 * 
 * @version		$Id: InterceptorFactory.php 1502 2008-06-09 21:21:10Z anders $
 */
abstract class InterceptorFactory {
	/**
	 * Private unused constructor to block instantiation of this class.
	 */
	private function __construct () {}

	/**
	 * Instantiates, initilizes and returns interceptors set to be used.
	 *
	 * @param array $interceptors Interceptor classes to instantiate.
	 * @param array $settings     Associative array with settings for interceptors.
	 * @return array An array with instantiated interceptors.
	 */
	public static function createInterceptors (array $interceptors) {
		$stack = array(); // To hold instantiated interceptors
		
		foreach ($interceptors as $name => $class) {
			$settings = Config::getInterceptorSettings($name);
			$instance = new $class($settings);

			$instance->init();
			$stack[] = $instance;
		}

		return $stack;
	}
}
?>
