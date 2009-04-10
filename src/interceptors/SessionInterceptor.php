<?php
require(ROOT . '/core/Session.php');

/**
 * Interceptor which prepares a session for use. A <code>Session</code> object is created and
 * injected into the <code>Request</code>.
 */
class SessionInterceptor extends AbstractInterceptor {
	/**
	 * Invokes and processes the interceptor.
	 */
	public function intercept ($dispatcher) {
		$session = new Session();
		$dispatcher->getRequest()->session = $session;
		return $dispatcher->invoke();
	}
}
?>
