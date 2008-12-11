<?php
/**
 * This class defines a <code>handle_error()</code> method designed to be set as the error handler
 * for PHP- and user-triggered errors.
 */
class ErrorHandler {
	private $action;
	private $request;
	private $result;
	private $view;
	private $handlingError;
	
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
		$this->handlingError = false;
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
		$reportLevel = error_reporting();
		if ($reportLevel == 0 || $this->handlingError) return;

		if ((Config::get('debug') && ($reportLevel & $errno)) || $errno == E_USER_ERROR) {
			// Rethrows the error as an exception
			throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
		}
		else {
			error_log($errstr . " ($errfile:$errline)");
		}
	}

	/**
	 * Handles uncaught exceptions.
	 */
	public function handleException ($exception) {
		$this->handlingError = true;
		$data = array('exception' => $exception, 'action' => $this->action);

		$admin = Config::get('admin');
		if (!Config::get('debug') && isset($admin)) {
			error_log($exception->getMessage() . " ({$exception->getFile()}:{$exception->getLine()})");

			$email = $this->generateMail($exception);
			$email->addRecipient($admin);
			$mailer = new MailService();
			$mailer->send($email);
		}

		// Try/catch this to avoid infinite loop in case the view doesn't exist
		try {
			$result = new $this->result($data, $this->view);
			$result->render($this->request);
		} catch (Exception $e) {
			echo '<strong>' . $e->getMessage() . '</strong><br />';
			echo 'Please configure the ErrorInterceptor to use an existing template';
		}
	}

	/**
	 * Prepares an email to an admin for the supplied exception.
	 *
	 * @param Exception $exception The exception.
	 * @return Email
	 */
	private function generateMail ($exception) {
		$email = new Email();
		$email->subject = 'Exception caught';
		$email->setBody(<<<TXT
{$exception->getMessage()}

Location:
{$exception->getFile()}:{$exception->getLine()}

Stacktrace:
{$exception->getTraceAsString()}

Request:
{$this->getVars($this->request->expose())}

Action Variables:
{$this->getVars($this->action)}
TXT
		);
		return $email;
	}

	/**
	 * Returns textual representation of the values in the passed array/object.
	 *
	 * @param mixed $vars Array or object to return values of.
	 * @return string
	 */
	private function getVars ($vars) {
		$txt = '';
		foreach ($vars as $var => $value) {
			$txt .= $var . ': ';
			$txt .= print_r($value, true);
			if (!isset($value)) $txt .= "\n";
		}
		return $txt;
	}
}
?>
