<?php
/**
 * Interceptor which checks to see if the user making this request is authenticated.
 *
 * If session is enabled, the target action is <code>AuthAware</code> and implements the
 * <code>allowedAccess()</code> method, the user is passed to the action for it to determine
 * whether to allow access.
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
		ClassLoader::load($this->userModel);
	}

	/**
	 * Invokes and processes the interceptor.
	 */
	public function intercept ($dispatcher) {
		$action = $dispatcher->getAction();
		$request = $dispatcher->getRequest();

		if ($request->hasSession()) {
			$user = $request->session[$this->userKey];

			if (!$this->isUserAuthenticated($user)) {
				if ($action instanceof MessageAware) {
					$action->msg->setMessage('You must log in to access
							the page you requested.', false);
				}

				return $this->denyAccess($request, true);
			}

			if ($action instanceof AuthAware) {
				if (method_exists($action, 'allowedAccess')) {
					if (!$action->allowedAccess($user)) {
						if (method_exists($action, 'denyAccess')) {
							return $action->denyAccess($request, $user);
						}

						return $this->denyAccess($request);
					}
				}
			}
		}
		return $dispatcher->invoke();
	}

	/**
	 * Makes sure the user value found in the session is of the correct type.
	 *
	 * @param mixed $user Session value to make sure represents a user.
	 * @return bool       TRUE if $user represents an authenticated user, FALSE if not.
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
	 *
	 * @param Request $request      The HTTP request.
	 * @param string $includeTarget Do we want to include a target parameter indicating the URL
	 *								that was the original target of the request?
	 */
	private function denyAccess ($request, $includeTarget = false) {
		$redirectTo = $this->loginUri;

		/*
		 * Adds a target-parameter with the originally requested URI, so the login action can
		 * redirect to the requested page after login. However, only do this if requested and
		 * only for GET-requests as redirect themselves are always GET.
		 */
		if ($includeTarget && $request->getMethod() === 'GET') {
			$queryString = $request->getQueryString();
			$target = $request->getUri()
					. urlencode((!empty($queryString) ? "?{$queryString}" : ''));
			$paramSeparator = (strpos($redirectTo, '?') === false ? '?' : '&');
			$redirectTo .= "{$paramSeparator}target={$target}";
		}

		return new RedirectResponse($redirectTo);
	}
}
?>
