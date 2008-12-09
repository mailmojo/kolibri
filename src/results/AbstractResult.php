<?php
/**
 * Abstract class providing the basic implementation of a result. A result represents the outcome of the
 * request performed, and a response will usually be rendered to the client over HTTP.
 */
abstract class AbstractResult implements Result {
	private $action;
	private $contentType;
	private $charset;
	
	/**
	 * Constructor.
	 *
	 * @param Request &$request		The request object representing the current HTTP request.
	 * @param string $content_type	The content type of the rendered result. Default is text/html.
	 * @param string $charset		The charset of the rendered result. Default is utf-8.
	 * @return BaseResult
	 */
	public function __construct ($action, $contentType = 'text/html', $charset = 'utf-8') {
		$this->action		= $action;
		$this->contentType	= $contentType;
		$this->charset		= $charset;
		
		header("Content-Type: $contentType; charset=$charset");
	}

	public function getAction () {
		return $this->action;
	}

	public function getActionData () {
		if (method_exists($this->action, 'expose')) {
			return $action->expose();
		}

		$data = array();
		foreach ($this->action as $key => $value) {
			$data[$key] = $value;
		}

		return $data;
	}
}
?>
