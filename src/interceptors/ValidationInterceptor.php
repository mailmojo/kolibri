<?php
/**
 * Interceptor handling model validation and its corresponding error messages.
 * 
 * The target action must be <code>ValidationAware</code> and have a fully populated model
 * which we are to validate in a public <code>$model</code> property. This model is usually
 * populated by a <code>ModelInterceptor</code>. If validation fails, the
 * <code>validationFailed()</code> method on the action is called for the action to determine
 * the response and set any custom error message.
 */
class ValidationInterceptor extends AbstractInterceptor {
	/**
	 * Invokes and processes the interceptor.
	 */
	public function intercept ($dispatcher) {
		// We never validate GET-requests, to allow actions with both GET and POST handlers
		if ($dispatcher->getRequest()->getMethod() === 'GET') {
			return $dispatcher->invoke();
		}

		$action = $dispatcher->getAction();
		$valid = true;
		
		// Validate model if action wants validation and a validateable model is prepared
		if ($action instanceof ValidationAware
				&& $action->model instanceof ValidateableModelProxy) {
			if (!$action->model->validate()) {
				$valid = false;
				// Retrieve the result we want to return
				$result = $action->validationFailed();
			}
		}

		if ($valid) {
			$result = $dispatcher->invoke();
		}
		else {
			/*
			 * If validationFailed() didn't set a specific message, we give a general
			 * error message.
			 */
			if ($action instanceof MessageAware && $action->msg->isEmpty()) {
				$action->msg->setMessage('Submitted form contains errors. Please correct
						any errors listed in the form and try again.', false);
			}

			/*
			 * If the response is a redirect and sessions are enabled, store model for
			 * retrieval after redirect.
			 */
			if ($result instanceof RedirectResponse && $dispatcher->getRequest()->hasSession()) {
				$dispatcher->getRequest()->session['model'] = $action->model;
			}
		}

		return $result;
	}
}
?>
