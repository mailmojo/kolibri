<?php
/**
 * Implementation of a result set where a file is returned to the client. The contents may come
 * from an actual file on the file system or data passed to this class when it is instantiated.
 * 
 * @version		$Id: RedirectResult.php 334 2006-05-21 20:42:54Z anders $
 */	
class FileResult extends BaseResult {
	var $mime;
	var $charset;
	var $output_name;
	var $data;
	var $data_is_file;
	
	/**
	 * Constructor.
	 * 
	 * @param string $mime			MIME-type of the file.
	 * @param string $charset		Charset of the file.
	 * @param string $data			Data contents to return directly or file name of the file.
	 * @param string $output_name	Optional name to give the returned file, if different from $file when
	 * 								referring to an actual file.
	 * @param bool $data_is_file	<code>TRUE</code> if $data is a file name, <code>FALSE</code> if not.
	 */
	function FileResult (&$request, $mime, $charset, $data, $output_name = null, $data_is_file = false) {
		parent::BaseResult($request);
		
		if (empty($data)) {
			trigger_error("FileResult: No data found.", E_USER_ERROR);
		}
		
		$this->mime = $mime;
		$this->charset = $charset;
		$this->data = $data;
		
		if (empty($output_name)) {
			// If actual file and no custom file name, set correct file name
			if ($data_is_file) {
				$this->output_name = basename($data);
			}
			else trigger_error("FileResult: No output file name given.", E_USER_ERROR);
		}
		else {
			$this->output_name = $output_name;
		}
	}
	
	/**
	 * Sends the file to the client.
	 */
	function render () {
		header("Content-Type: {$this->mime}; charset={$this->charset}");
		header("Content-Disposition: attachment; filename=\"{$this->output_name}\"");
		
		if ($this->data_is_file && is_file($this->data)) {
			header('Content-Length: ' . filesize($this->data));
			readfile($this->data);
		}
		else {
			header('Content-Length: ' . mb_strlen($this->data));
			echo $this->data;
		}
	}
}
?>