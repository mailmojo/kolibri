<?php
/**
 * Interceptor which checks to see if the user making this request is authenticated.
 *
 * If the target action is <code>AuthAware</code> and implements the <code>allowedAccess()</code> method,
 * the user is passed to the action for it to determine whether to allow access.
 * 
 * @version		$Id: AuthInterceptor.php 1537 2008-08-02 19:22:57Z anders $
 */
class AuthInterceptor extends AbstractInterceptor {
	/**
	 * Name of the user model to use.
	 * @var string
	 */
	protected $userModel;

	/**
	 * Name of the session key for the user model.
	 * @var string
	 */
	protected $userKey;

	/**
	 * URI of the login page.
	 * @var string
	 */
	protected $loginUri;

	/**
	 * Initialize this interceptor by making sure the user model has been included.
	 */
	public function init () {
		Autoloader::load($this->userModel);
	}

	/**
	 * Invokes and processes the interceptor.
	 */
	public function intercept ($dispatcher) {
		$action = $dispatcher->getAction();
		$user = $action->session[$this->userKey];

		if (!$this->isUserAuthenticated($user)) {
			$action->session['target'] = $dispatcher->getRequest()->getUri();
			if ($action instanceof MessageAware) {
				$action->msg->setMessage('Vennligst logg inn for å komme til siden du forespurte.', false);
			}

			return $this->denyAccess($dispatcher);
		}

		if ($action instanceof AuthAware) {
			if (method_exists($action, 'allowedAccess')) {
				if (!$action->allowedAccess($user)) {
					if (method_exists($action, 'denyAccess')) {
						return $action->denyAccess($user);
					}

					return $this->denyAccess($dispatcher);
				}
			}
		}
		return $dispatcher->invoke();
	}

	/**
	 * Makes sure the user value found in the session is of the correct type.
	 *
	 * @param mixed $user	Session value to make sure represents a user.
	 * @return bool			TRUE if $user represents an authenticated user, FALSE if not.
	 */
	private function isUserAuthenticated ($user) {
		if ($user !== null) {
			if ($user instanceof ModelProxy) {
				if ($user->extract() instanceof $this->userModel) {
					return true;
				}
			}
			else if ($user instanceof $this->userModel) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Denies access by redirecting to the configured login URI.
	 */
	private function denyAccess ($dispatcher) {
		return new RedirectResult($dispatcher->getAction(), $this->loginUri);
	}
}
?>
