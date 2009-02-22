<?php
/**
 * This result renders a JSON response with data to the client.
 */	
class JsonResult implements Result {
	private $data;
	private $charset;

	/**
	 * Constructor.
	 *
	 * @param mixed $data     The data en encode in the JSON response..
	 * @param string $charset Charset of the data. Defaults to utf-8.
	 */
	public function __construct ($data, $charset = 'utf-8') {	
		$this->data    = $data;
		$this->charset = $charset;
	}

	/**
	 * Performs the JSON encoding and returns the result.
	 *
	 * @param Request $request	The request. Disregarded in this implementation.
	 */
	public function render ($request) {
		header("Content-Type: application/json; charset=$this->charset");
		echo json_encode($this->data);
	}
}
?>
