<?php
/**
 * This response renders the data as a JSON string to the client.
 */	
class JsonResponse extends Response {
	/**
	 * Initialize this response.
	 *
	 * @param mixed $data     The data en encode in the JSON response.
	 * @param int $status     HTTP status code. Defaults to 200 OK.
	 * @param string $charset Charset of the data. Defaults to utf-8.
	 */
	public function __construct ($data, $status = 200, $charset = 'utf-8') {
		parent::__construct($data, $status, 'application/json', $charset);	
	}

	/**
	 * Performs the JSON encoding and outputs the result.
	 */
	public function render ($request) {
		$this->sendHeaders();
		echo json_encode($this->data);
	}
}
?>
