<?php
require(ROOT . '/lib/Message.php');

/**
 * Interceptor which provides the target action, if <code>MessageAware</code>, with a facility
 * to give the user status messages.
 */
class MessageInterceptor extends AbstractInterceptor {
	/**
	 * Invokes and processes this interceptor.
	 */
	public function intercept ($dispatcher) {
		$action = $dispatcher->getAction();

		if ($action instanceof MessageAware) {
			// If a previous message is set in the session, put it into the action
			if ($action instanceof SessionAware && isset($action->session['message'])) {
				$action->msg = $action->session['message'];
				$action->session->remove('message');
			}
			// Otherwise create a new instance
			else {
				$action->msg = Message::getInstance();
			}
		}

		$result = $dispatcher->invoke();

		/*
		 * If we are about to redirect and a message has been set, save it temporarily in the
		 * session so it can be retrieved in the new location.
		 */
		if ($result instanceof RedirectResult
				&& $action instanceof SessionAware
				&& $action instanceof MessageAware
				&& !$action->msg->isEmpty()) {
			$action->session['message'] = $action->msg;
		}

		return $result;
	}
}
?>
