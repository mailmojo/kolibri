<?php
/**
 * Interceptor which loads utils listed in a <code>loadUtils</code> config setting.
 * 
 * @version		$Id: UtilsInterceptor.php 1510 2008-06-17 05:45:50Z anders $
 */
class UtilsInterceptor extends AbstractInterceptor {
	/**
	 * Invokes and processes this interceptor.
	 */
	public function intercept ($dispatcher) {
		$utils = Config::get('loadUtils');
		if (is_array($utils)) {
			foreach ($utils as $util) {
				Utils::import($util);
			}
		}

		return $dispatcher->invoke();
	}
}
?>
