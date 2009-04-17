<?php
/**
 * Provides the implementation of a response using a PHP file as a template for creating
 * the actual output.
 */	
class PhpResponse extends Response {
	private $phpTemplate;

	/**
	 * Initializes this response.
	 *
	 * @param mixed $data         Data to expose to the PHP template.
	 * @param string $phpTemplate PHP template to use, relative to VIEW_PATH, omitting the
	 *                            extension.
	 * @param int $status         HTTP status code. Defaults to 200 OK.
	 * @param string $contentType Content type of the response. Defaults to text/html.
	 */
	public function __construct ($data, $phpTemplate, $status = 200,
			$contentType = 'text/html') {
		parent::__construct($data, $status, $contentType);
		$this->phpTemplate = VIEW_PATH . "$phpTemplate.php";

		if (!file_exists($this->phpTemplate)) {
			throw new Exception("PHP template ({$this->phpTemplate}) does not exist");
		}
	}

	/**
	 * Extracts the data into a sandboxed function scope, while providing direct access to the
	 * request object and the application configuration. This sandboxed scope is made available
	 * to the PHP template file by including it directly, and collecting it's output which is
	 * thus used as the results output.
	 */
	public function render ($request) {
		$data = array();
		foreach ($this->data as $key => $value) {
			$data[$key] = $value;
		}

		/*
		 * Create a sandbox function which extracts all data to it's local scope, instead of
		 * letting the view template run inside the PhpResult object scope.
		 */
		$sandbox = create_function('$request, $config, $_d, $_t', 'extract($_d); include($_t);');
		$sandbox($request, Config::get(), $data, $this->phpTemplate);
	}
}
?>
