<?php
require(ROOT . '/lib/Message.php');

/**
 * Interceptor which provides the target action, if <code>MessageAware</code>, with a facility
 * to give the user status messages. This interceptor should be defined early in the
 * interceptor stack, but after the <code>SessionInterceptor</code>, as many interceptors
 * make use of messages if availible.
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

		/*
		 * Checks to see if the session has a message stored while the action do not. If so,
		 * the message is injected into the action message and removed from the session.
		 */
		if ($dispatcher->getRequest()->hasSession()
				&& $action instanceof MessageAware
				&& $action->msg->isEmpty()) {
			$request = $dispatcher->getRequest();
			$msgFromSession = $request->session->get('message');
			if ($msgFromSession !== null) {
				$action->msg = $msgFromSession;
				$request->session->remove('message');
			}
		}

		return $result;
	}
}
?>
