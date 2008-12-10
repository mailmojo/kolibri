<?php
require(ROOT . '/lib/ErrorHandler.php');

/**
 * Interceptor which prepares and sets an error handler. Any errors triggered or exceptions thrown after
 * this interceptor is invoked will be handled by the error handler defined by this interceptor, unless
 * otherwised catched (for exceptions).
 */
class ErrorInterceptor extends AbstractInterceptor {
	/**
	 * Type of Result (class name) to display error.
	 * @var string
	 */
	protected $result;

	/**
	 * File name of the error view, relative to <code>VIEW_PATH</code>, excluding extension.
	 * @var string
	 */
	protected $view;

	/**
	 * Intercepts the request to add an error handler. The error handler we set is implemented by
	 * the <code>ErrorHandler</code> class.
	 */
	public function intercept ($dispatcher) {
		$errorHandler = new ErrorHandler($dispatcher->getAction(), $dispatcher->getRequest(),
				$this->result, $this->view);
		// It seems we must set the exception handler before the error handler
		set_exception_handler(array($errorHandler, 'handleException'));
		set_error_handler(array($errorHandler, 'handleError'));

		return $dispatcher->invoke();
	}
}
?>
