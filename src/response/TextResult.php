<?php
/**
 * Provides the implementation of a result set which sets the content-type to plain text. This
 * will ensure that all output will be sent to the client as-is.
 */	
class TextResult extends AbstractResult {
	private $text;
	
	/**
	 * Constructor.
	 *
	 * @param object $action The current action.
	 * @param string $text   Text to send to client.
	 */
	public function __construct ($action, $text) {
		parent::__construct($action, 'text/plain');
		$this->text = $text;
	}

	public function render ($request) {
		echo $this->text;
	}
}
?>
