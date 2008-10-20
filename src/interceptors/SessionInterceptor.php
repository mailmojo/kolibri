<?php
require(ROOT . '/core/Session.php');

/**
 * Interceptor which prepares a session for use. A <code>Session</code> object is created and injected
 * into the <code>Request</code>.
 *
 * If the target action is <code>SessionAware</code> the session data is set on the action as well.
 * 
 * @version		$Id: SessionInterceptor.php 1518 2008-06-30 23:43:38Z anders $
 */
class SessionInterceptor extends AbstractInterceptor {
	/**
	 * Instantiates a <code>Session</code> object and injects it into the request. If the action is
	 * <code>SessionAware</code>, pass in session data.
	 */
	public function intercept ($dispatcher) {
		$action = $dispatcher->getAction();

//		$dispatcher->getRequest()->setSession($session);

		if ($action instanceof SessionAware) {
			$session = new Session();
			$action->session = $session;
		}

		return $dispatcher->invoke();
	}
}
?>
