<?php
/**
 * This class defines a <code>handle_error()</code> method designed to be set as the error handler
 * for PHP- and user-triggered errors.
 *
 * TODO: Currently the error view must be XSL. Change to PHP? Or user-choosable?
 * 
 * @version		$Id: ErrorHandler.php 1531 2008-07-30 13:31:28Z frode $
 */
class ErrorHandler {
	private $action;
	private $request;
	private $result;
	private $view;
	
	/**
	 * Creates an instance of this class.
	 *
	 * @param object $action The action that is the target of the current request.
	 * @param Request $request The request object.
	 * @param string $result   Class name of the result to use when displaying an error.
	 * @param string $view     File name of the view to render with the $result.
	 */
	public function __construct ($action, $request, $result, $view) {
		$this->action  = $action;
		$this->request = $request;
		$this->result  = $result;
		$this->view    = $view;
	}
	
	/**
	 * Handles PHP errors or errors triggered by <code>trigger_error()</code>. We rethrow the
	 * error as an exception so don't have to maintain two separate code paths.
	 *
	 * @param string $errno   Number of the error.
	 * @param string $errstr  Message of the error.
	 * @param string $errfile File the error occured in.
	 * @param string $errline Line number where the occured.
	 */
	public function handleError ($errno, $errstr, $errfile, $errline) {
		// Rethrows the error as an exception
		throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
	}

	/**
	 * Handles uncaught exceptions.
	 */
	public function handleException ($exception) {
		// XXX: Following is supposed to work around bug with backtrace of ErrorException in 5.2
		//if ($exception instanceof ErrorException) {
		//	for ($i = count($backtrace) - 1; $i > 0; --$i) {
		//		$backtrace[$i]['args'] = $backtrace[$i - 1]['args'];
		//	}
		//}
		$data = array('exception' => $exception, 'action' => $this->action);
		$result = new $this->result($data, $this->view);

		try {
			$result->render($this->request);
		} catch (Exception $e) {
			echo '<strong>' . $e->getMessage() . '</strong><br />';
			echo 'Please configure the ErrorInterceptor to use an existing template';
		}
	}
}
?>
