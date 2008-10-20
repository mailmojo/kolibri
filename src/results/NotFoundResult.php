<?php
/**
 * Provides the implementation of a result set which sends a 404 Not Found HTTP status header to
 * the client. A  XSL template is used to render a viewable response for the client.
 * 
 * @version		$Id: NotFoundResult.php 1542 2008-08-12 18:46:42Z anders $
 */	
class NotFoundResult extends XsltResult {
	
	/**
	 * Constructor.
	 *
	 * @param string $xsl_template	Name of XSL file (excluding the extension) to use, relative to
	 * 								the views-directory.
	 */
	public function __construct ($action, $xslTemplate = '/404') {
		parent::__construct($action, $xslTemplate);
		header("HTTP/1.1 404 Not Found");
	}
}
?>
