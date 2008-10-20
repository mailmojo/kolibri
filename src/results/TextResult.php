<?php
/**
 * Provides the implementation of a result set which sets the content-type to plain text. This
 * will ensure that all output will be sent to the client as-is.
 * 
 * @version		$Id: TextResult.php 1539 2008-08-04 16:50:10Z frode $
 */	
class TextResult extends AbstractResult {
	private $text;
	
	/**
	 * Constructor.
	 *
	 * @param Request &$request		A reference to the request object representing the current
	 * 								HTTP request.
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