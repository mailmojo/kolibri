<?php
/**
 * Interceptor which puts session and/or request parameters into corresponding properties of
 * the target action if <code>ParametersAware</code>.
 */
class ParametersInterceptor extends AbstractInterceptor {
	/**
	 * Invokes and processes the interceptor.
	 */
	public function intercept ($dispatcher) {
		$action = $dispatcher->getAction();

		if ($action instanceof ParametersAware) {
			$request = $dispatcher->getRequest();

			$this->populate($action, $request->params);

			if ($request->hasSession()) {
				$this->populate($action, $request->session);
			}
		}

		return $dispatcher->invoke();
	}

	/**
	 * Loops through $params and populates related properties in $action.
	 */
	private function populate ($action, $params) {
		foreach ($params as $param => $value) {
			if (property_exists($action, $param)) {
				$action->$param = $value;
			}
		}
	}
}
?>
