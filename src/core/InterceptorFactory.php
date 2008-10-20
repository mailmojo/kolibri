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
	 * @param array $interceptors	Interceptor classes to instantiate.
	 * @return array				An array with instantiated interceptors.
	 */
	public static function createInterceptors ($interceptors) {
		$stack = array(); // To hold instantiated interceptors

		foreach ($interceptors as $class) {
			if (is_array($class)) {
				// $class contains parameters to be passed to the constructor
				$actualClass = key($class);
				$instance = new $actualClass(current($class));
			}
			else {
				$instance = new $class();
			}

			$instance->init();
			$stack[] = $instance;
		}

		return $stack;
	}
}
?>
