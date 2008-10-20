<?php
/**
 * Interceptor handling model validation and its corresponding error messages.
 * 
 * The target action must be <code>ValidationAware</code> and return a fully populated model which
 * we are to validate. This model is usually populated by a <code>ModelInterceptor</code>. If any
 * validation errors occures, error messages are put into the action so the view can display them.
 * 
 * @version		$Id: ValidatorInterceptor.php 1526 2008-07-14 16:07:05Z anders $
 */
class ValidatorInterceptor extends AbstractInterceptor {
	/**
	 * Invokes and processes the interceptor.
	 */
	public function intercept ($dispatcher) {
		$action = $dispatcher->getAction();
		
		if ($action instanceof ValidationAware && $dispatcher->getRequest()->getMethod() == 'POST'
				&& isset($action->model) && is_object($action->model)) {
			/*
			 * Action is ValidationAware, request is POSTed and a model is prepared. Create a validator,
			 * do the validation and put errors into the action.
			 */
			$conf = Config::getValidationConfig();
			$validator = new Validator($conf['classes'], $conf['messages']);
			$action->errors = $validator->validate($action->model);
		}

		$result = $dispatcher->invoke();

		if ($action instanceof ValidationAware && $action instanceof MessageAware) {
			// Report errors if action has any errors registered
			if (!empty($action->errors)) {
				$action->msg->setMessage('Submitted form contains errors. Please correct any errors listed
							in the form and try again.', false);
			}
		}

		return $result;
//		TODO: Do we want to fix this? Errors in session is pretty useless as is below, as the invalid data
//			isn't present after a redirect. If we do want this, submitted model data should probably be stored.
//		$this->checkErrorsInSession($action);
//		return $result;
	}

	/**
	 * Checks to see if the session has error messages stored while the action do not. If so, the
	 * errors are injected into the action and removed from the session.
	 */
//	private function checkErrorsInSession ($dispatcher) {
//		$session = $dispatcher->getRequest()->getSession();
//
//		if ($session !== null) {
//			$action = $dispatcher->getAction();
//
//			if ($action instanceof ValidationAware && empty($action->errors)) {
//				$errorsInSession = $session->get('errors');
//
//				if (!empty($errorsInSession)) {
//					$action->errors = $errorsInSession;
//					$session->remove('errors');
//				}
//			}
//		}
//	}
}
?>
