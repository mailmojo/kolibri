<?php
require(ROOT . '/lib/Message.php');

/**
 * Interceptor which provides the target action, if <code>MessageAware</code>, with a facility to give
 * the user status messages. This interceptor should be defined early in the interceptor stack,
 * ideally after the <code>SessionInterceptor</code> and before the <code>ErrorInterceptor</code>.
 * 
 * @version		$Id: MessageInterceptor.php 1518 2008-06-30 23:43:38Z anders $
 */
class MessageInterceptor extends AbstractInterceptor {
	/**
	 * Invokes and processes this interceptor.
	 */
	public function intercept ($dispatcher) {
		$action = $dispatcher->getAction();

		if ($action instanceof MessageAware) {
			$action->msg = Message::getInstance();
		}

		$result = $dispatcher->invoke();
		$this->checkMessageInSession($action);
		return $result;
	}

	/**
	 * Checks to see if the session has a message stored while the action do not. If so, the
	 * message is injected into the action message and removed from the session.
	 */
	private function checkMessageInSession ($action) {
		if ($action instanceof SessionAware && $action instanceof MessageAware && $action->msg->isEmpty()) {
			if (isset($action->session['message'])) {
				$action->msg = $action->session['message'];
				unset($action->session['message']);
			}
		}
	}
}
?>
