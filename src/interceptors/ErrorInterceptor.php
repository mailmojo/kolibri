<?php
require(ROOT . '/lib/ErrorHandler.php');

/**
 * Interceptor which prepares and sets an error handler. Any errors triggered or exceptions thrown after
 * this interceptor is invoked will be handled by the error handler defined by this interceptor, unless
 * otherwised catched (for exceptions).
 * 
 * @version		$Id: ErrorInterceptor.php 1531 2008-07-30 13:31:28Z frode $
 */
class ErrorInterceptor extends AbstractInterceptor {
	/**
	 * Filename of the error view.
	 * @var string
	 */
	protected $errorView;

	/**
	 * Intercepts the request to add an error handler.
	 * 
	 * The error handler we set is implemented by the <code>ErrorHandler</code> class, and will be
	 * used for all errors triggered from this point on.
	 */
	public function intercept ($dispatcher) {
		$errorHandler = new ErrorHandler($dispatcher->getAction(), $this->errorView);
		set_error_handler(array($errorHandler, 'handleError'));
		return $dispatcher->invoke();
	}
}
?>
