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
	private $view;
	
	/**
	 * Creates an instance of this class.
	 *
	 * @return ErrorHandler
	 */
	public function __construct ($action, $view) {
		$this->action = $action;
		$this->view = $view;
	}
	
	/**
	 * Handles PHP errors or errors triggered by <code>trigger_error()</code>.
	 * 
	 * This implementation creates an XML representation of the error. The error is then
	 * transformed by an XSL stylesheet to render an response to the client, before the execution
	 * of the application is discontinued.
	 *
	 * @param string $errno		Number of the error.
	 * @param string $errstr	Message of the error.
	 * @param string $errfile	File the error occured in.
	 * @param string $errline	Line number where the occured.
	 * @param array $errcontext	Array with the variables in scope at the time of the error.
	 */
	public function handleError ($errno, $errstr, $errfile, $errline, $errcontext) {
		if ($errno == 2048) return; // For now, just ignore notices

		$debug = Config::get('debug'); // Is debug mode enabled?

		if ($errno & (E_USER_NOTICE | E_USER_WARNING)
				|| ($debug === true && $errno & (E_NOTICE | E_WARNING))) {
			// The error is an user notice/warning, or a PHP notice/warning when debug is on
			$this->action->msg->setMessage("$errstr ($errfile:$errline)", false);
			return;
		}

		// Create XML and XSLT utilities
		$generator = new XmlGenerator();
		$transformer = new XslTransformer($this->view);

		$error = array(
				'id'		=> $errno,
				'message'	=> $errstr,
				'location'	=> "$errfile:$errline"
		);

		// Only include the stack if debug is enabled
		if ($debug === true || $debug == 'true') {
			$error['stack'] = var_export($errcontext, true);
		}

		$generator->append($error);
		$xml = $generator->build('error');
		$transformer->addXml($xml);
		$result = $transformer->process();

		// Render the result to the client, and discontinue execution of TURBO
		echo $result;
		exit;
	}
}
?>
