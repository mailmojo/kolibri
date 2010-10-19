<?php
require(ROOT . '/lib/ErrorHandler.php');

/**
 * Interceptor which prepares and sets an error handler. Any errors triggered or exceptions
 * thrown after this interceptor is invoked will be handled by the error handler defined by
 * this interceptor, unless otherwised catched (for exceptions).
 *
 * TODO: Pluggable error handler. Specify class name by config option?
 */
class ErrorInterceptor extends AbstractInterceptor {
	/**
	 * Type of Response (class name) to display error.
	 * @var string
	 */
	protected $response;

	/**
	 * File name of the error view, relative to <code>VIEW_PATH</code>, excluding extension.
	 * @var string
	 */
	protected $view;

	/**
	 * Content type to specify for the response when rendering an error.
	 */
	protected $contentType;

	/**
	 * Intercepts the request to add handlers. The error handler we set is implemented by
	 * the <code>ErrorHandler</code> class.
	 */
	public function intercept ($dispatcher) {
		$errorHandler = new ErrorHandler($dispatcher->getAction(), $dispatcher->getRequest(),
				$this->response, $this->view, $this->contentType);
		set_exception_handler(array($errorHandler, 'handleException'));
		set_error_handler(array($errorHandler, 'handleError'));

		return $dispatcher->invoke();
	}
}
?>
