<?php
/**
 * This class defines our custom error- and exception handling methods. If debug is disabled the
 * error is logged and an email sent to the defined admin address, else the error should be
 * displayed in full. How exactly the error is displayed depends on the result configured and
 * the error view used.
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
	 * @param object $action   The action that is the target of the current request.
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
	 * error as an exception so we don't have to maintain two separate code paths.
	 *
	 * @param string $errno   Number of the error.
	 * @param string $errstr  Message of the error.
	 * @param string $errfile File the error occured in.
	 * @param string $errline Line number where the occured.
	 */
	public function handleError ($errno, $errstr, $errfile, $errline) {
		$reportLevel = error_reporting();

		// Only handle error if not already handling, is not silenced (@) and error level is enabled
		if (!$this->handlingError && $reportLevel != 0 && ($reportLevel & $errno)) {
			throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
		}
	}

	/**
	 * Handles uncaught exceptions. Script execution is always stopped after this method is executed.
	 *
	 * @param Exception $exception The uncaught exception to handle.
	 */
	public function handleException ($exception) {
		$this->handlingError = true;
		$data = array('exception' => $exception, 'action' => $this->action);
		$logging = Config::get('logging');

		if (!empty($logging) && isset($logging['level']) && $logging['level'] == true) {
			if (!empty($logging['file']) && is_writable($logging['file'])) {
				error_log($exception->getMessage() . " ({$exception->getFile()}:{$exception->getLine()})",
					3, $logging['file']);
			}
			else {
				error_log($exception->getMessage() . " ({$exception->getFile()}:{$exception->getLine()})");
			}

			if (!empty($logging['email'])) {
				// Send email to admin
				$email = $this->generateMail($exception);
				$email->addRecipient($logging['email']);
				$mailer = new MailService();
				$mailer->send($email);
			}
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
		$email->subject = $_SERVER['SERVER_NAME'] . ': Exception caught';
		$email->setBody(<<<TXT
{$exception}

Request:
{$this->getVars($this->request)}

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
	 * @param int $indent Indentation level. Used to indent properly in recursion.
	 * @return string
	 */
	private function getVars ($vars, $indent = 0) {
		$txt = '';
		foreach ($vars as $var => $value) {
			$txt .= str_repeat('    ', $indent) . $var . ': ';
			if ($value === null || is_scalar($value)) {
				$txt .= ($value !== null ? $value : 'NULL') . "\n";
			}
			else {
				$txt .= (is_object($value) ? get_class($value) : gettype($value)) . " {\n";
				$txt .= $this->getVars($value, $indent + 1) . str_repeat('    ', $indent) . "}\n";
			}
		}
		return $txt;
	}
}
?>
