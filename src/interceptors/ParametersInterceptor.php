<?php
/**
 * Interceptor which puts session and/or request parameters into corresponding properties of the target
 * action if <code>ParametersAware</code>.
 * 
 * @version		$Id: ParametersInterceptor.php 1518 2008-06-30 23:43:38Z anders $
 */
class ParametersInterceptor extends AbstractInterceptor {
	/**
	 * Invokes and processes the interceptor.
	 */
	public function intercept ($dispatcher) {
		$action = $dispatcher->getAction();

		if ($action instanceof ParametersAware) {
			if ($action instanceof SessionAware) {
				$this->populate($action, $action->session);
			}

			$this->populate($action, $dispatcher->getRequest()->getAll());
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
