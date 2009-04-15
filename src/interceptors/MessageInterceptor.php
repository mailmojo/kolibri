<?php
require(ROOT . '/lib/Message.php');

/**
 * Interceptor which provides the target action, if <code>MessageAware</code>, with a facility
 * to give the user status messages. The facility is set in an implicit <code>$msg</code>
 * property on the action.
 */
class MessageInterceptor extends AbstractInterceptor {
	/**
	 * Invokes and processes this interceptor.
	 */
	public function intercept ($dispatcher) {
		$action = $dispatcher->getAction();
		$request = $dispatcher->getRequest();

		if ($action instanceof MessageAware) {
			// If a previous message is set in the session, put it into the action
			if ($request->hasSession() && isset($request->session['message'])) {
				$action->msg = $request->session['message'];
				$request->session->remove('message');
			}
			// Otherwise create a new instance
			else {
				$action->msg = Message::getInstance();
			}
		}

		$result = $dispatcher->invoke();

		/*
		 * If we are about to redirect and a message has been set, save it temporarily in the
		 * session (if present) so it can be retrieved in the new location.
		 */
		if ($result instanceof RedirectResponse
				&& $action instanceof MessageAware
				&& !$action->msg->isEmpty()
				&& $request->hasSession()) {
			$request->session['message'] = $action->msg;
		}

		return $result;
	}
}
?>
