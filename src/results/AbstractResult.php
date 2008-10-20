<?php
/**
 * Abstract class providing the basic implementation of a result. A result represents the outcome of the
 * request performed, and a response will usually be rendered to the client over HTTP.
 * 
 * @version		$Id: AbstractResult.php 1495 2008-05-16 18:00:13Z anders $
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
}
?>
