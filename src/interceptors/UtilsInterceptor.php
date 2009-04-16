<?php
/**
 * Interceptor which loads utils listed in a <code>loadUtils</code> config setting.
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
